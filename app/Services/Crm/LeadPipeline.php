<?php

namespace App\Services\Crm;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Role;
use App\Models\Lead;
use App\Models\ReferralCode;
use App\Models\User;
use App\Services\WhatsApp\WhatsApp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeadPipeline
{
    public function __construct(private WhatsApp $whatsApp) {}

    /** A new registration (Google Forms, site form or manual entry) enters the pipeline as Pending. */
    public function intake(array $data, LeadSource $source, ?User $by = null): Lead
    {
        $lead = Lead::create([
            ...$data,
            'source' => $source,
            'status' => LeadStatus::Pending,
        ]);

        $lead->activities()->create([
            'user_id' => $by?->id,
            'type' => 'created',
            'to_status' => LeadStatus::Pending->value,
            'body' => 'تم استلام التسجيل عبر '.$source->getLabel(),
        ]);

        $this->whatsApp->send(
            $lead->phone,
            "أهلاً {$lead->name} 👋\nشكراً لتسجيلك في أكاديمية كودكس. سيتواصل معك فريقنا خلال 24 ساعة لتحديد موعد اختبار المستوى.",
            'lead.registered',
            $lead,
        );

        return $lead;
    }

    /**
     * Move a lead along the pipeline. Dropping out always needs a reason, and
     * sales-entered drop-outs wait for an admin approval (RBAC matrix).
     */
    public function transition(Lead $lead, LeadStatus $to, ?User $by = null, array $data = []): Lead
    {
        $from = $lead->status;

        if ($to === LeadStatus::DroppedOut && blank($data['dropout_reason_id'] ?? $lead->dropout_reason_id)) {
            throw ValidationException::withMessages([
                'dropout_reason_id' => 'يجب تحديد سبب الانسحاب قبل نقل الطالب إلى "منسحب".',
            ]);
        }

        return DB::transaction(function () use ($lead, $from, $to, $by, $data) {
            $lead->fill(collect($data)->only([
                'dropout_reason_id', 'dropout_notes', 'assessment_at', 'assessment_level', 'notes',
            ])->filter(fn ($value) => $value !== null)->all());

            $lead->status = $to;

            if ($from === LeadStatus::Pending && $to->stopsSla()) {
                $lead->first_contacted_at ??= now();
            }

            if ($to === LeadStatus::DroppedOut) {
                $autoApprove = $by?->isSuperAdmin() ?? false;
                $lead->dropout_approved_at = $autoApprove ? now() : null;
                $lead->dropout_approved_by = $autoApprove ? $by->id : null;
            }

            if ($to === LeadStatus::Enrolled && ! $lead->user_id) {
                $lead->user_id = $this->convertToStudent($lead)?->id;
            }

            $lead->save();

            if ($from !== $to) {
                $lead->activities()->create([
                    'user_id' => $by?->id,
                    'type' => 'status_change',
                    'from_status' => $from?->value,
                    'to_status' => $to->value,
                    'body' => $data['note'] ?? null,
                ]);
            }

            return $lead;
        });
    }

    public function approveDropout(Lead $lead, User $admin): void
    {
        $lead->update(['dropout_approved_at' => now(), 'dropout_approved_by' => $admin->id]);

        $lead->activities()->create([
            'user_id' => $admin->id,
            'type' => 'note',
            'body' => 'تم اعتماد سبب الانسحاب: '.($lead->dropoutReason?->name ?? '—'),
        ]);
    }

    /** Flag pending leads that passed the SLA window and alert management. */
    public function flagSlaBreaches(): int
    {
        $count = 0;

        Lead::query()->breachingSla()->with('assignee')->each(function (Lead $lead) use (&$count) {
            $lead->forceFill(['is_overdue' => true, 'overdue_notified_at' => now()])->save();

            $lead->activities()->create([
                'type' => 'sla_breach',
                'body' => 'تجاوز مؤشر الـ SLA دون تواصل — نُقل إلى قائمة المتأخرين.',
            ]);

            $recipients = User::role(Role::SuperAdmin->value)->get();
            if ($lead->assignee) {
                $recipients->push($lead->assignee);
            }

            Notification::make()
                ->title('تأخر التواصل مع طالب جديد')
                ->body("{$lead->name} تجاوز مهلة الـ 24 ساعة دون تواصل.")
                ->danger()
                ->icon('heroicon-o-bell-alert')
                ->actions([
                    Action::make('view')->label('فتح الملف')->url(route('filament.admin.resources.leads.view', $lead)),
                ])
                ->sendToDatabase($recipients->unique('id'));

            $this->whatsApp->toAdmins("⚠️ تأخر SLA: {$lead->name} ({$lead->phone}) لم يتم التواصل معه خلال المهلة.", 'lead.sla_breached', $lead);

            $count++;
        });

        return $count;
    }

    protected function convertToStudent(Lead $lead): ?User
    {
        if (blank($lead->email)) {
            return null;
        }

        $user = User::firstOrCreate(['email' => $lead->email], [
            'name' => $lead->name,
            'phone' => $lead->phone,
            'password' => Hash::make(Str::random(16)),
        ]);

        $user->assignRole(Role::Student->value);
        ReferralCode::issueFor($user);

        if ($lead->course_id) {
            $user->enrollments()->firstOrCreate(['course_id' => $lead->course_id], ['enrolled_at' => now()]);
        }

        return $user;
    }
}

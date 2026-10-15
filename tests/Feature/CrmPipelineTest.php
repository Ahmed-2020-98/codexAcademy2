<?php

namespace Tests\Feature;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Role;
use App\Livewire\LeadForm;
use App\Models\DropoutReason;
use App\Models\Lead;
use App\Models\WhatsappMessage;
use App\Services\Crm\LeadPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CrmPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        config(['academy.google_forms_secret' => 'secret-123']);
    }

    public function test_google_forms_webhook_creates_pending_lead_with_24h_sla(): void
    {
        $this->freezeTime();

        $response = $this->postJson('/api/webhooks/google-forms', [
            'name' => 'سارة أحمد',
            'phone' => '+201001234567',
            'email' => 'sara@example.com',
            'response_id' => 'resp-1',
        ], ['X-Codex-Secret' => 'secret-123']);

        $response->assertCreated()->assertJsonPath('ok', true);

        $lead = Lead::sole();
        $this->assertSame(LeadStatus::Pending, $lead->status);
        $this->assertSame(LeadSource::GoogleForms, $lead->source);
        $this->assertSame(now()->addHours(24)->timestamp, $lead->sla_due_at->timestamp);
        $this->assertSame('created', $lead->activities()->sole()->type);
        $this->assertSame(1, WhatsappMessage::where('event', 'lead.registered')->count());
    }

    public function test_webhook_rejects_wrong_secret_and_is_idempotent(): void
    {
        $payload = ['name' => 'Test', 'phone' => '+201000000001', 'response_id' => 'dup'];

        $this->postJson('/api/webhooks/google-forms', $payload, ['X-Codex-Secret' => 'nope'])->assertUnauthorized();
        $this->postJson('/api/webhooks/google-forms', $payload, ['X-Codex-Secret' => 'secret-123'])->assertCreated();
        $this->postJson('/api/webhooks/google-forms', $payload, ['X-Codex-Secret' => 'secret-123'])->assertOk()->assertJsonPath('duplicate', true);

        $this->assertSame(1, Lead::count());
    }

    public function test_sla_check_moves_uncontacted_leads_to_overdue_and_notifies_admins(): void
    {
        $admin = $this->userWithRole(Role::SuperAdmin);
        $pipeline = app(LeadPipeline::class);

        $late = $pipeline->intake(['name' => 'Late', 'phone' => '+20111'], LeadSource::Site);
        $fresh = $pipeline->intake(['name' => 'Fresh', 'phone' => '+20112'], LeadSource::Site);
        $contacted = $pipeline->intake(['name' => 'Contacted', 'phone' => '+20113'], LeadSource::Site);
        $pipeline->transition($contacted, LeadStatus::AssessmentScheduled, $admin, ['assessment_at' => now()->addDay()]);

        $this->travel(25)->hours();
        $fresh->update(['sla_due_at' => now()->addHour()]);

        $this->artisan('crm:check-sla')->assertSuccessful();

        $this->assertTrue($late->fresh()->is_overdue);
        $this->assertFalse($fresh->fresh()->is_overdue);
        $this->assertFalse($contacted->fresh()->is_overdue);
        $this->assertSame(1, Lead::overdue()->count());
        $this->assertSame(1, $admin->notifications()->count());

        // Running again does not notify twice.
        $this->artisan('crm:check-sla');
        $this->assertSame(1, $admin->fresh()->notifications()->count());
    }

    public function test_dropping_out_requires_a_reason(): void
    {
        $lead = app(LeadPipeline::class)->intake(['name' => 'X', 'phone' => '+20114'], LeadSource::Site);

        $this->expectException(ValidationException::class);
        app(LeadPipeline::class)->transition($lead, LeadStatus::DroppedOut, $this->userWithRole(Role::Sales));
    }

    public function test_sales_dropout_waits_for_admin_approval_but_admin_dropout_is_auto_approved(): void
    {
        $reason = DropoutReason::create(['name' => 'السعر']);
        $pipeline = app(LeadPipeline::class);
        $sales = $this->userWithRole(Role::Sales);
        $admin = $this->userWithRole(Role::SuperAdmin);

        $bySales = $pipeline->intake(['name' => 'A', 'phone' => '+20115'], LeadSource::Site);
        $pipeline->transition($bySales, LeadStatus::DroppedOut, $sales, ['dropout_reason_id' => $reason->id]);
        $this->assertNull($bySales->fresh()->dropout_approved_at);
        $this->assertNotNull($bySales->fresh()->first_contacted_at, 'Any status change stops the SLA clock.');

        $this->assertFalse($sales->can('approveDropout', $bySales));
        $this->assertTrue($admin->can('approveDropout', $bySales));
        $pipeline->approveDropout($bySales, $admin);
        $this->assertSame($admin->id, $bySales->fresh()->dropout_approved_by);

        $byAdmin = $pipeline->intake(['name' => 'B', 'phone' => '+20116'], LeadSource::Site);
        $pipeline->transition($byAdmin, LeadStatus::DroppedOut, $admin, ['dropout_reason_id' => $reason->id]);
        $this->assertNotNull($byAdmin->fresh()->dropout_approved_at);
    }

    public function test_enrolling_a_lead_creates_a_student_account_with_referral_code(): void
    {
        $course = $this->makeCourse();
        $lead = app(LeadPipeline::class)->intake(['name' => 'New', 'phone' => '+20117', 'email' => 'new@example.com', 'course_id' => $course->id], LeadSource::Site);

        app(LeadPipeline::class)->transition($lead, LeadStatus::Enrolled, $this->userWithRole(Role::Sales));

        $student = $lead->fresh()->user;
        $this->assertNotNull($student);
        $this->assertTrue($student->isStudent());
        $this->assertNotNull($student->referralCode);
        $this->assertTrue($student->isEnrolledIn($course));
    }

    public function test_public_registration_form_creates_a_site_lead(): void
    {
        Livewire::test(LeadForm::class)
            ->set('name', 'محمد علي')
            ->set('phone', '+20 100 555 6677')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('تم استلام تسجيلك');

        $this->assertSame(LeadSource::Site, Lead::sole()->source);
    }
}

<?php

namespace App\Livewire;

use App\Enums\CourseType;
use App\Enums\LeadSource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lead;
use App\Services\Crm\LeadPipeline;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;

/** Public registration form → Pending lead in the CRM (same intake as Google Forms). */
class LeadForm extends Component
{
    #[Validate('required|string|min:3|max:255', as: 'الاسم')]
    public string $name = '';

    #[Validate('required|string|min:8|max:32|regex:/^[+0-9 ]+$/', as: 'رقم الهاتف')]
    public string $phone = '';

    #[Validate('nullable|email|max:255', as: 'البريد الإلكتروني')]
    public string $email = '';

    public ?string $department_id = null;

    #[Url(as: 'course')]
    public ?string $courseSlug = null;

    public ?string $course_id = null;

    public string $study_mode = '';

    #[Validate('nullable|string|max:1000', as: 'الملاحظات')]
    public string $notes = '';

    public ?string $referral = null;

    public ?int $leadId = null;

    public function mount(): void
    {
        $this->referral = request()->query('ref');

        if ($this->courseSlug && $course = Course::where('slug', $this->courseSlug)->first()) {
            $this->course_id = (string) $course->id;
            $this->department_id = (string) $course->department_id;
            $this->study_mode = $course->type->value;
        }
    }

    public function updatedDepartmentId(): void
    {
        $this->course_id = null;
    }

    public function submit(LeadPipeline $pipeline): void
    {
        $this->validate();
        $this->validate([
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'course_id' => ['nullable', Rule::exists('courses', 'id')],
            'study_mode' => ['nullable', Rule::enum(CourseType::class)],
        ]);

        // Avoid duplicates when someone submits twice in a row.
        $existing = Lead::where('phone', $this->phone)->where('created_at', '>=', now()->subDay())->first();

        $lead = $existing ?? $pipeline->intake([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'department_id' => $this->department_id ?: null,
            'course_id' => $this->course_id ?: null,
            'study_mode' => $this->study_mode ?: null,
            'notes' => trim($this->notes.($this->referral ? "\nكود إحالة: {$this->referral}" : '')) ?: null,
        ], $this->referral ? LeadSource::Referral : LeadSource::Site);

        $this->leadId = $lead->id;
    }

    public function render()
    {
        return view('livewire.lead-form', [
            'departments' => Department::active()->get(),
            'courses' => Course::published()
                ->when($this->department_id, fn ($q) => $q->where('department_id', $this->department_id))
                ->orderBy('title')->get(['id', 'title', 'type']),
            'types' => CourseType::cases(),
            'lead' => $this->leadId ? Lead::find($this->leadId) : null,
        ]);
    }
}

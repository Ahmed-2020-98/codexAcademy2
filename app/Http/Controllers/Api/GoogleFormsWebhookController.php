<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourseType;
use App\Enums\LeadSource;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lead;
use App\Services\Crm\LeadPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives a Google Forms submission (posted by Apps Script) and drops it into
 * the CRM as a Pending lead, which starts the 24H SLA timer.
 */
class GoogleFormsWebhookController extends Controller
{
    public function __invoke(Request $request, LeadPipeline $pipeline): JsonResponse
    {
        abort_unless(hash_equals((string) config('academy.google_forms_secret'), (string) $request->header('X-Codex-Secret')), 401, 'Invalid secret');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'study_mode' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'response_id' => ['nullable', 'string', 'max:255'],
        ]);

        // Google may retry a delivery; the form response id keeps intake idempotent.
        if (! empty($data['response_id']) && $existing = Lead::where('payload->response_id', $data['response_id'])->first()) {
            return response()->json(['ok' => true, 'lead_id' => $existing->id, 'duplicate' => true]);
        }

        $course = filled($data['course'] ?? null)
            ? Course::where('title', $data['course'])->orWhere('slug', $data['course'])->first()
            : null;
        $department = filled($data['department'] ?? null)
            ? Department::where('name', $data['department'])->orWhere('name_en', $data['department'])->orWhere('slug', $data['department'])->first()
            : $course?->department;

        $lead = $pipeline->intake([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'course_id' => $course?->id,
            'department_id' => $department?->id,
            'study_mode' => CourseType::tryFrom((string) ($data['study_mode'] ?? '')) ?? $course?->type,
            'notes' => $data['notes'] ?? null,
            'payload' => $request->all(),
        ], LeadSource::GoogleForms);

        return response()->json(['ok' => true, 'lead_id' => $lead->id, 'sla_due_at' => $lead->sla_due_at->toIso8601String()], 201);
    }
}

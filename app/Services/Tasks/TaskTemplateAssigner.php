<?php

namespace App\Services\Tasks;

use App\Models\Group;
use App\Models\StudentTask;
use App\Models\TaskTemplate;
use App\Models\User;

/**
 * Applies a template to a whole cohort with chunked bulk inserts, so a group
 * of 500+ students is handled in a handful of queries.
 */
class TaskTemplateAssigner
{
    public function assignToGroup(TaskTemplate $template, Group $group, ?User $by = null, ?string $startOn = null): int
    {
        $items = $template->items()->get();
        $start = $startOn ? now()->parse($startOn) : ($group->starts_on ?? now())->copy();
        $created = 0;
        $now = now();

        $group->students()->chunkById(250, function ($students) use ($items, $template, $group, $by, $start, $now, &$created) {
            $rows = [];

            foreach ($students as $student) {
                foreach ($items as $item) {
                    $rows[] = [
                        'user_id' => $student->id,
                        'group_id' => $group->id,
                        'task_template_id' => $template->id,
                        'week_no' => $item->week_no,
                        'title' => $item->title,
                        'description' => $item->description,
                        'due_on' => $start->copy()->addWeeks($item->week_no - 1)->endOfWeek()->toDateString(),
                        'assigned_by' => $by?->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($rows, 1000) as $chunk) {
                StudentTask::insert($chunk);
            }

            $created += count($rows);
        }, 'users.id', 'id');

        return $created;
    }
}

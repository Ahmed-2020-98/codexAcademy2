<?php

namespace App\Console\Commands;

use App\Models\StudentTask;
use App\Models\User;
use App\Services\WhatsApp\WhatsApp;
use Illuminate\Console\Command;

class SendWeeklyTaskReminders extends Command
{
    protected $signature = 'academy:weekly-task-reminders';

    protected $description = 'WhatsApp each student a summary of this week\'s open tasks';

    public function handle(WhatsApp $whatsApp): int
    {
        $sent = 0;

        StudentTask::query()
            ->whereNull('completed_at')
            ->whereBetween('due_on', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->selectRaw('user_id, COUNT(*) as open_count')
            ->groupBy('user_id')
            ->get()
            ->each(function ($row) use ($whatsApp, &$sent) {
                $student = User::find($row->user_id);
                $whatsApp->send(
                    $student?->phone,
                    "صباح الخير {$student?->name} ☀️ لديك {$row->open_count} مهام هذا الأسبوع في «مهامي». تابع تقدمك من لوحة الطالب: ".route('learn.tasks'),
                    'tasks.weekly_reminder',
                    $student,
                );
                $sent++;
            });

        $this->info("Reminded {$sent} student(s).");

        return self::SUCCESS;
    }
}

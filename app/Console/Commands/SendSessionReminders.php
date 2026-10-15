<?php

namespace App\Console\Commands;

use App\Enums\SessionStatus;
use App\Models\GroupSession;
use App\Services\WhatsApp\WhatsApp;
use Illuminate\Console\Command;

class SendSessionReminders extends Command
{
    protected $signature = 'academy:session-reminders';

    protected $description = 'WhatsApp reminders 24h and 1h before live/offline sessions';

    public function handle(WhatsApp $whatsApp): int
    {
        $windows = [
            'reminded_24h_at' => [now()->addHours(23), now()->addHours(24), 'غداً'],
            'reminded_1h_at' => [now(), now()->addHour(), 'خلال ساعة'],
        ];

        $sent = 0;

        foreach ($windows as $column => [$from, $to, $when]) {
            GroupSession::query()
                ->where('status', SessionStatus::Scheduled)
                ->whereNull($column)
                ->whereBetween('starts_at', [$from, $to])
                ->with('group.students', 'group.course')
                ->each(function (GroupSession $session) use ($whatsApp, $column, $when, &$sent) {
                    $link = $session->joinUrl() ?: $session->group->location;

                    foreach ($session->group->students as $student) {
                        $whatsApp->send(
                            $student->phone,
                            "تذكير: محاضرة «{$session->title}» في {$session->group->course->title} {$when} — {$session->starts_at->translatedFormat('l h:i A')}\n{$link}",
                            'session.reminder',
                            $session,
                        );
                        $sent++;
                    }

                    $session->forceFill([$column => now()])->save();
                });
        }

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}

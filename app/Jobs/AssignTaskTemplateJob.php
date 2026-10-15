<?php

namespace App\Jobs;

use App\Models\Group;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\Tasks\TaskTemplateAssigner;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignTaskTemplateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TaskTemplate $template,
        public Group $group,
        public ?User $by = null,
        public ?string $startOn = null,
    ) {}

    public function handle(TaskTemplateAssigner $assigner): void
    {
        $count = $assigner->assignToGroup($this->template, $this->group, $this->by, $this->startOn);

        if ($this->by) {
            Notification::make()
                ->title('تم إسناد المهام')
                ->body("أُسندت {$count} مهمة لطلاب «{$this->group->name}» من قالب «{$this->template->name}».")
                ->success()
                ->sendToDatabase($this->by);
        }
    }
}

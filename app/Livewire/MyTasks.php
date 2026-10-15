<?php

namespace App\Livewire;

use App\Models\StudentTask;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** "My Tasks": weekly checklist with an automatic progress bar. */
#[Layout('components.layouts.learn')]
class MyTasks extends Component
{
    #[Url]
    public ?int $week = null;

    public function mount(): void
    {
        $this->week ??= $this->currentWeek();
    }

    protected function currentWeek(): int
    {
        return (int) (StudentTask::where('user_id', auth()->id())
            ->where('due_on', '>=', now()->startOfWeek()->toDateString())
            ->orderBy('due_on')
            ->value('week_no')
            ?? StudentTask::where('user_id', auth()->id())->max('week_no')
            ?? 1);
    }

    public function toggle(int $taskId): void
    {
        $task = StudentTask::findOrFail($taskId);
        $this->authorize('toggle', $task);

        $task->update(['completed_at' => $task->completed_at ? null : now()]);
    }

    public function render()
    {
        $all = StudentTask::where('user_id', auth()->id())->with('group')->orderBy('week_no')->orderBy('id')->get();
        $weeks = $all->groupBy('week_no')->map(fn ($tasks) => [
            'total' => $tasks->count(),
            'done' => $tasks->whereNotNull('completed_at')->count(),
        ]);
        $tasks = $all->where('week_no', $this->week)->values();

        return view('livewire.my-tasks', [
            'weeks' => $weeks,
            'tasks' => $tasks,
            'overall' => $all->count() ? (int) round($all->whereNotNull('completed_at')->count() / $all->count() * 100) : 0,
            'weekPct' => $tasks->count() ? (int) round($tasks->whereNotNull('completed_at')->count() / $tasks->count() * 100) : 0,
        ])->title('مهامي');
    }
}

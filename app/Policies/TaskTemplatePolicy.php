<?php

namespace App\Policies;

use App\Models\TaskTemplate;
use App\Models\User;

class TaskTemplatePolicy
{
    use Concerns;

    public function viewAny(User $user): bool
    {
        return $this->instructor($user);
    }

    public function view(User $user, TaskTemplate $template): bool
    {
        return $this->instructor($user);
    }

    public function create(User $user): bool
    {
        return $this->instructor($user);
    }

    public function update(User $user, TaskTemplate $template): bool
    {
        return $this->instructor($user) && $template->created_by === $user->id;
    }

    public function delete(User $user, TaskTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\ProjectTemplate;
use App\Models\User;

class ProjectTemplatePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function update(User $user, ProjectTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function delete(User $user, ProjectTemplate $template): bool
    {
        return $user->id === $template->user_id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }
}

<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\User;

class ChildPolicy
{
    public function view(User $user, Child $child): bool
    {
        return $user->family_id === $child->family_id;
    }

    public function update(User $user, Child $child): bool
    {
        return $user->family_id === $child->family_id;
    }

    public function delete(User $user, Child $child): bool
    {
        return $user->family_id === $child->family_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Child;
use App\Models\User;

class ChildPolicy
{
    /**
     * Admins may manage the children of every family; returning null for
     * everyone else falls through to the per-ability family checks below.
     */
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

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

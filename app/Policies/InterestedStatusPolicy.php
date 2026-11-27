<?php

namespace App\Policies;

use App\Models\InterestedStatus;
use App\Models\User;

class InterestedStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_interested::status');
    }

    public function view(User $user, InterestedStatus $status): bool
    {
        return $user->can('view_interested::status');
    }

    public function create(User $user): bool
    {
        return $user->can('create_interested::status');
    }

    public function update(User $user, InterestedStatus $status): bool
    {
        return $user->can('update_interested::status');
    }

    public function delete(User $user, InterestedStatus $status): bool
    {
        return $user->can('delete_interested::status');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_interested::status');
    }
}

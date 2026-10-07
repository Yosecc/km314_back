<?php

namespace App\Policies;

use App\Models\InterestedLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InterestedLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_interested::level');
    }

    public function view(User $user, InterestedLevel $interestedLevel): bool
    {
        return $user->can('view_interested::level');
    }

    public function create(User $user): bool
    {
        return $user->can('create_interested::level');
    }

    public function update(User $user, InterestedLevel $interestedLevel): bool
    {
        return $user->can('update_interested::level');
    }

    public function delete(User $user, InterestedLevel $interestedLevel): bool
    {
        return $user->can('delete_interested::level');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_interested::level');
    }
}

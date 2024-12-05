<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StartUpOption;
use Illuminate\Auth\Access\HandlesAuthorization;

class StartUpOptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_start::up::option');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StartUpOption $startUpOption): bool
    {
        return $user->can('view_start::up::option');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_start::up::option');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StartUpOption $startUpOption): bool
    {
        return $user->can('update_start::up::option');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StartUpOption $startUpOption): bool
    {
        return $user->can('delete_start::up::option');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_start::up::option');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, StartUpOption $startUpOption): bool
    {
        return $user->can('force_delete_start::up::option');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_start::up::option');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, StartUpOption $startUpOption): bool
    {
        return $user->can('restore_start::up::option');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_start::up::option');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, StartUpOption $startUpOption): bool
    {
        return $user->can('replicate_start::up::option');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_start::up::option');
    }
}

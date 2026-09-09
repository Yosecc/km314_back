<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PackageReception;
use Illuminate\Auth\Access\HandlesAuthorization;

class PackageReceptionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_package::reception');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PackageReception $packageReception): bool
    {
        return $user->can('view_package::reception');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_package::reception');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PackageReception $packageReception): bool
    {
        return $packageReception->status === PackageReception::EXPECTED
            && $user->can('update_package::reception');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PackageReception $packageReception): bool
    {
        return $user->can('delete_package::reception');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_package::reception');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PackageReception $packageReception): bool
    {
        return $user->can('force_delete_package::reception');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_package::reception');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PackageReception $packageReception): bool
    {
        return $user->can('restore_package::reception');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_package::reception');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PackageReception $packageReception): bool
    {
        return $user->can('replicate_package::reception');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_package::reception');
    }

    public function receive(User $user, PackageReception $packageReception): bool
    {
        return $packageReception->status === PackageReception::EXPECTED
            && $user->can('receive_package::reception');
    }

    public function deliver(User $user, PackageReception $packageReception): bool
    {
        return $packageReception->status === PackageReception::RECEIVED
            && $user->can('deliver_package::reception');
    }

    public function cancel(User $user, PackageReception $packageReception): bool
    {
        return $packageReception->status === PackageReception::EXPECTED
            && $user->can('cancel_package::reception');
    }

    public function viewSensitive(User $user, PackageReception $packageReception): bool
    {
        return $user->can('view_sensitive_package::reception');
    }
}

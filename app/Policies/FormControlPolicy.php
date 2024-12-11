<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FormControl;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormControlPolicy
{
    use HandlesAuthorization;

    public function aprobar(User $user): bool
    {
        return $user->can('aprobar_form::control');
    }

	public function rechazar(User $user): bool
    {
        return $user->can('rechazar_form::control');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_form::control');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FormControl $formControl): bool
    {
        return $user->can('view_form::control');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_form::control');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FormControl $formControl): bool
    {
        return $user->can('update_form::control');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FormControl $formControl): bool
    {
        return $user->can('delete_form::control');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_form::control');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, FormControl $formControl): bool
    {
        return $user->can('force_delete_form::control');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_form::control');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, FormControl $formControl): bool
    {
        return $user->can('restore_form::control');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_form::control');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, FormControl $formControl): bool
    {
        return $user->can('replicate_form::control');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_form::control');
    }
}

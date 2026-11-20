<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_booking');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->can('view_booking');
    }

    public function create(User $user): bool
    {
        return $user->can('create_booking');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->can('update_booking');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->can('delete_booking');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_booking');
    }

    public function forceDelete(User $user, Booking $booking): bool
    {
        return $user->can('force_delete_booking');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_booking');
    }

    public function restore(User $user, Booking $booking): bool
    {
        return $user->can('restore_booking');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_booking');
    }

    public function replicate(User $user, Booking $booking): bool
    {
        return $user->can('replicate_booking');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_booking');
    }
}

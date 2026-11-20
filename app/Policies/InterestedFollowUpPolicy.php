<?php

namespace App\Policies;

use App\Models\InterestedFollowUp;
use App\Models\User;

class InterestedFollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_interested::follow::up');
    }

    public function view(User $user, InterestedFollowUp $followUp): bool
    {
        return $user->can('view_interested::follow::up');
    }

    public function create(User $user): bool
    {
        return $user->can('create_interested::follow::up');
    }

    public function update(User $user, InterestedFollowUp $followUp): bool
    {
        return $user->can('update_interested::follow::up');
    }

    public function delete(User $user, InterestedFollowUp $followUp): bool
    {
        return $user->can('delete_interested::follow::up');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_interested::follow::up');
    }

    public function complete(User $user, InterestedFollowUp $followUp): bool
    {
        return $user->can('complete_interested::follow::up');
    }

    public function reschedule(User $user, InterestedFollowUp $followUp): bool
    {
        return $user->can('reschedule_interested::follow::up');
    }
}

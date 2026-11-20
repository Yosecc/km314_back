<?php

namespace App\Policies;

use App\Models\InterestedContact;
use App\Models\User;

class InterestedContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_interested::contact');
    }

    public function view(User $user, InterestedContact $contact): bool
    {
        return $user->can('view_interested::contact');
    }

    public function create(User $user): bool
    {
        return $user->can('create_interested::contact');
    }

    public function update(User $user, InterestedContact $contact): bool
    {
        return $user->can('update_interested::contact');
    }

    public function delete(User $user, InterestedContact $contact): bool
    {
        return $user->can('delete_interested::contact');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_interested::contact');
    }
}

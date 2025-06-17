<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $currentRole = session('current_role');
        return in_array($currentRole, ['admin', 'director']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        $currentRole = session('current_role');
        return in_array($currentRole, ['admin', 'director']);
    }
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $currentUser, User $userToUpdate)
    {
        $currentRole = session('current_role');

        if ($currentRole === 'admin') return true;
        if ($currentRole === 'director') {
            return !$userToUpdate->roles->contains('nombre', 'admin');
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $currentUser, User $userToDelete)
    {
        return $this->update($currentUser, $userToDelete);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}

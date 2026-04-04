<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ThesisRegistration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ThesisRegistrationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ThesisRegistration');
    }

    public function view(AuthUser $authUser, ThesisRegistration $thesisRegistration): bool
    {
        return $authUser->can('View:ThesisRegistration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ThesisRegistration');
    }

    public function update(AuthUser $authUser, ThesisRegistration $thesisRegistration): bool
    {
        return $authUser->can('Update:ThesisRegistration');
    }

    public function delete(AuthUser $authUser, ThesisRegistration $thesisRegistration): bool
    {
        return $authUser->can('Delete:ThesisRegistration');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ThesisRegistration');
    }

    public function restore(AuthUser $authUser, ThesisRegistration $thesisRegistration): bool
    {
        return $authUser->can('Restore:ThesisRegistration');
    }

    public function forceDelete(AuthUser $authUser, ThesisRegistration $thesisRegistration): bool
    {
        return $authUser->can('ForceDelete:ThesisRegistration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ThesisRegistration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ThesisRegistration');
    }

    public function replicate(AuthUser $authUser, ThesisRegistration $thesisRegistration): bool
    {
        return $authUser->can('Replicate:ThesisRegistration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ThesisRegistration');
    }
}

<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SuspensionRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuspensionRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SuspensionRequest');
    }

    public function view(AuthUser $authUser, SuspensionRequest $suspensionRequest): bool
    {
        return $authUser->can('View:SuspensionRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SuspensionRequest');
    }

    public function update(AuthUser $authUser, SuspensionRequest $suspensionRequest): bool
    {
        return $authUser->can('Update:SuspensionRequest');
    }

    public function delete(AuthUser $authUser, SuspensionRequest $suspensionRequest): bool
    {
        return $authUser->can('Delete:SuspensionRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SuspensionRequest');
    }

    public function restore(AuthUser $authUser, SuspensionRequest $suspensionRequest): bool
    {
        return $authUser->can('Restore:SuspensionRequest');
    }

    public function forceDelete(AuthUser $authUser, SuspensionRequest $suspensionRequest): bool
    {
        return $authUser->can('ForceDelete:SuspensionRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SuspensionRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SuspensionRequest');
    }

    public function replicate(AuthUser $authUser, SuspensionRequest $suspensionRequest): bool
    {
        return $authUser->can('Replicate:SuspensionRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SuspensionRequest');
    }

}
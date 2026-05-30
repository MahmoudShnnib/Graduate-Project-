<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\AddDropRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddDropRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AddDropRequest');
    }

    public function view(AuthUser $authUser, AddDropRequest $addDropRequest): bool
    {
        return $authUser->can('View:AddDropRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AddDropRequest');
    }

    public function update(AuthUser $authUser, AddDropRequest $addDropRequest): bool
    {
        return $authUser->can('Update:AddDropRequest');
    }

    public function delete(AuthUser $authUser, AddDropRequest $addDropRequest): bool
    {
        return $authUser->can('Delete:AddDropRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AddDropRequest');
    }

    public function restore(AuthUser $authUser, AddDropRequest $addDropRequest): bool
    {
        return $authUser->can('Restore:AddDropRequest');
    }

    public function forceDelete(AuthUser $authUser, AddDropRequest $addDropRequest): bool
    {
        return $authUser->can('ForceDelete:AddDropRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AddDropRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AddDropRequest');
    }

    public function replicate(AuthUser $authUser, AddDropRequest $addDropRequest): bool
    {
        return $authUser->can('Replicate:AddDropRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AddDropRequest');
    }

}
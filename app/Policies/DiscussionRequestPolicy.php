<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DiscussionRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DiscussionRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DiscussionRequest');
    }

    public function view(AuthUser $authUser, DiscussionRequest $discussionRequest): bool
    {
        return $authUser->can('View:DiscussionRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DiscussionRequest');
    }

    public function update(AuthUser $authUser, DiscussionRequest $discussionRequest): bool
    {
        return $authUser->can('Update:DiscussionRequest');
    }

    public function delete(AuthUser $authUser, DiscussionRequest $discussionRequest): bool
    {
        return $authUser->can('Delete:DiscussionRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DiscussionRequest');
    }

    public function restore(AuthUser $authUser, DiscussionRequest $discussionRequest): bool
    {
        return $authUser->can('Restore:DiscussionRequest');
    }

    public function forceDelete(AuthUser $authUser, DiscussionRequest $discussionRequest): bool
    {
        return $authUser->can('ForceDelete:DiscussionRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DiscussionRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DiscussionRequest');
    }

    public function replicate(AuthUser $authUser, DiscussionRequest $discussionRequest): bool
    {
        return $authUser->can('Replicate:DiscussionRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DiscussionRequest');
    }
}

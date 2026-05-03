<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CourseOffering;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CourseOfferingPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CourseOffering');
    }

    public function view(AuthUser $authUser, CourseOffering $courseOffering): bool
    {
        if (! $authUser->can('View:CourseOffering')) {
            return false;
        }

        if ($authUser->role === 'faculty') {
            return $courseOffering->faculty_id === $authUser->id;
        }

        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CourseOffering');
    }

    public function update(AuthUser $authUser, CourseOffering $courseOffering): bool
    {
        if (! $authUser->can('Update:CourseOffering')) {
            return false;
        }

        if ($authUser->role === 'faculty') {
            return $courseOffering->faculty_id === $authUser->id;
        }

        return true;
    }

    public function delete(AuthUser $authUser, CourseOffering $courseOffering): bool
    {
        return $authUser->can('Delete:CourseOffering');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CourseOffering');
    }

    public function restore(AuthUser $authUser, CourseOffering $courseOffering): bool
    {
        return $authUser->can('Restore:CourseOffering');
    }

    public function forceDelete(AuthUser $authUser, CourseOffering $courseOffering): bool
    {
        return $authUser->can('ForceDelete:CourseOffering');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CourseOffering');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CourseOffering');
    }

    public function replicate(AuthUser $authUser, CourseOffering $courseOffering): bool
    {
        return $authUser->can('Replicate:CourseOffering');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CourseOffering');
    }
}

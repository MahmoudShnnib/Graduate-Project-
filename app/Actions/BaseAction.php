<?php

namespace App\Actions;

use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Access\Response;

class BaseAction
{
    public function can(): Response
    {
        return Gate::allow(class_basename(static::class));
    }
    // table permissions
    //
}

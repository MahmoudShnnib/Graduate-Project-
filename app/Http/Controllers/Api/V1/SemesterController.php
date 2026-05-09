<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SemesterResource;
use App\Models\Semester;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SemesterController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return SemesterResource::collection(
            Semester::query()->orderByDesc('start_date')->get()
        );
    }

    public function active(): SemesterResource
    {
        return new SemesterResource(
            Semester::query()->where('is_active', true)->firstOrFail()
        );
    }
}

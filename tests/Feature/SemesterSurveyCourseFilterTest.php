<?php

use App\Filament\Resources\Semesters\SemesterResource;
use App\Models\Course;
use App\Models\Specialization;

it('keeps a selected course in the options even when filtered to another specialization', function () {
    $specA = Specialization::factory()->create();
    $specB = Specialization::factory()->create();

    $courseB = Course::factory()->create(['is_active' => true]);
    $courseB->specializations()->attach($specB);

    // Filtering by specialization A would normally hide course B (it belongs to B),
    // which made Filament reject the already-selected course as "غير صالح".
    $options = SemesterResource::surveyCourseOptions($specA->id, [$courseB->id]);

    expect($options)->toHaveKey($courseB->id);
});

it('filters the visible courses by specialization when nothing is selected', function () {
    $specA = Specialization::factory()->create();
    $specB = Specialization::factory()->create();

    $courseA = Course::factory()->create(['is_active' => true]);
    $courseA->specializations()->attach($specA);
    $courseB = Course::factory()->create(['is_active' => true]);
    $courseB->specializations()->attach($specB);

    $options = SemesterResource::surveyCourseOptions($specA->id, []);

    expect($options)->toHaveKey($courseA->id)
        ->and($options)->not->toHaveKey($courseB->id);
});

it('marks a kept-but-out-of-filter selected course so it is not mistaken for one in the filtered specialization', function () {
    $specA = Specialization::factory()->create();
    $specB = Specialization::factory()->create();

    $courseA = Course::factory()->create(['is_active' => true]);
    $courseA->specializations()->attach($specA);
    $courseB = Course::factory()->create(['is_active' => true]);
    $courseB->specializations()->attach($specB);

    // Filter to spec A while course B (belongs to spec B) is already selected.
    $options = SemesterResource::surveyCourseOptions($specA->id, [$courseB->id]);

    expect($options[$courseB->id])->toContain('محدّد — من تخصص آخر')
        ->and($options[$courseA->id])->not->toContain('محدّد — من تخصص آخر');
});

it('does not mark any course when no specialization filter is active', function () {
    $spec = Specialization::factory()->create();
    $course = Course::factory()->create(['is_active' => true]);
    $course->specializations()->attach($spec);

    $options = SemesterResource::surveyCourseOptions(null, [$course->id]);

    expect($options[$course->id])->not->toContain('محدّد — من تخصص آخر');
});

it('excludes inactive courses', function () {
    $active = Course::factory()->create(['is_active' => true]);
    $inactive = Course::factory()->create(['is_active' => false]);

    $options = SemesterResource::surveyCourseOptions(null, []);

    expect($options)->toHaveKey($active->id)
        ->and($options)->not->toHaveKey($inactive->id);
});

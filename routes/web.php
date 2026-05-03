<?php

use App\Http\Controllers\ApplicationTrackingController;
use App\Http\Controllers\PrintCourseStudentsController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('filament.admin.pages.dashboard');
});
Route::get('/apply', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/apply', [RegistrationController::class, 'store'])->name('registration.store');
Route::get('/apply/sample', [RegistrationController::class, 'sample'])
    ->middleware('throttle:20,1')
    ->name('registration.sample');
Route::get('/apply/track', [ApplicationTrackingController::class, 'show'])->name('application.track.show');
Route::post('/apply/track', [ApplicationTrackingController::class, 'track'])
    ->middleware('throttle:5,1')
    ->name('application.track');

Route::get('/admin/course-offerings/{courseOffering}/print-students', PrintCourseStudentsController::class)
    ->middleware(['auth'])
    ->name('course-offering.print-students');

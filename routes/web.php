<?php

use App\Http\Controllers\ApplicationTrackingController;
use App\Http\Controllers\PrintController;
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
Route::post('/apply/otp/send', [RegistrationController::class, 'sendOtp'])
    ->middleware('throttle:6,1')
    ->name('registration.otp.send');
Route::post('/apply/otp/verify', [RegistrationController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('registration.otp.verify');
Route::get('/apply/track', [ApplicationTrackingController::class, 'show'])->name('application.track.show');
Route::post('/apply/track', [ApplicationTrackingController::class, 'track'])
    ->middleware('throttle:5,1')
    ->name('application.track');

Route::get('/admin/course-offerings/{courseOffering}/print-students', PrintCourseStudentsController::class)
    ->middleware(['auth'])
    ->name('course-offering.print-students');

Route::get('/admin/students/{student}/print-student-letter', [PrintController::class, 'studentLetter'])
    ->middleware(['auth'])
    ->name('print.student-letter');

Route::get('/admin/students/{student}/print-provisional-grades', [PrintController::class, 'provisionalGrades'])
    ->middleware(['auth'])
    ->name('print.provisional-grades');

Route::get('/admin/course-offerings/{courseOffering}/print-exam-attendance', [PrintController::class, 'examAttendance'])
    ->middleware(['auth'])
    ->name('print.exam-attendance');

Route::get('/admin/course-offerings/{courseOffering}/print-course-results', [PrintController::class, 'courseResults'])
    ->middleware(['auth'])
    ->name('print.course-results');

Route::get('/admin/graduates/{discussionRequest}/print-graduation-certificate', [PrintController::class, 'graduationCertificate'])
    ->middleware(['auth'])
    ->name('print.graduation-certificate');

Route::get('/admin/thesis-registrations/{thesisRegistration}/print-ministry-form', [PrintController::class, 'thesisMinistryForm'])
    ->middleware(['auth'])
    ->name('print.thesis-ministry-form');

Route::get('/admin/exam-list/{specialization}/print', [PrintController::class, 'examList'])
    ->middleware(['auth'])
    ->name('print.exam-list');

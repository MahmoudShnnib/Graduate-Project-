<?php

use App\Http\Controllers\ApplicationTrackingController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PrintCourseStudentsController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('registration.create');
});
Route::get('/apply', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/apply', [RegistrationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('registration.store');
Route::post('/apply/otp/send', [RegistrationController::class, 'sendOtp'])
    ->middleware('throttle:6,1')
    ->name('registration.otp.send');
Route::post('/apply/otp/verify', [RegistrationController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('registration.otp.verify');
Route::post('/apply/telegram/init', [RegistrationController::class, 'telegramInit'])
    ->middleware('throttle:6,1')
    ->name('registration.telegram.init');
Route::get('/apply/telegram/status', [RegistrationController::class, 'telegramStatus'])
    ->middleware('throttle:60,1')
    ->name('registration.telegram.status');
// Public WhatsApp-OTP password reset. Two entry points so each panel asks for the
// right identifier: students by national ID (/portal), staff by email (/admin). The
// OTP always goes to the phone ALREADY on file for the matched account — never a typed
// number. See _brain/project-context/password-management-guide.md.
Route::get('/portal/forgot-password', [PasswordResetController::class, 'show'])
    ->defaults('mode', 'student')
    ->name('password.forgot.student');
Route::get('/admin/forgot-password', [PasswordResetController::class, 'show'])
    ->defaults('mode', 'admin')
    ->name('password.forgot.admin');
Route::post('/forgot-password/send-otp', [PasswordResetController::class, 'sendOtp'])
    ->middleware('throttle:6,1')
    ->name('password.otp.send');
Route::post('/forgot-password/reset', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:10,1')
    ->name('password.reset');

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

// Exam prints are filter-aware: both accept optional ?specialization= and
// ?semester= query params (omit specialization to print every specialization).
Route::get('/admin/exam-list/print', [PrintController::class, 'examList'])
    ->middleware(['auth'])
    ->name('print.exam-list');

Route::get('/admin/exam-results/print', [PrintController::class, 'examResults'])
    ->middleware(['auth'])
    ->name('print.exam-results');

Route::get('/portal/student-courses/{student}/print', [PrintController::class, 'studentCourses'])
    ->middleware(['auth'])
    ->name('print.student-courses');

Route::get('/portal/clearance/{student}/print', [PrintController::class, 'clearanceForm'])
    ->middleware(['auth'])
    ->name('print.clearance-form');

// Blank clearance template (no student) — printed in bulk from the admin Graduates page.
Route::get('/admin/clearance-form/print', [PrintController::class, 'clearanceFormBlank'])
    ->middleware(['auth'])
    ->name('print.clearance-blank');

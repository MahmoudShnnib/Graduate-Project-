<?php

use App\Http\Controllers\Api\V1\AddDropController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClearanceController;
use App\Http\Controllers\Api\V1\CourseTreeController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\ExceptionalRequestController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SemesterController;
use App\Http\Controllers\Api\V1\SurveyController;
use App\Http\Controllers\Api\V1\SuspensionController;
use App\Http\Controllers\Api\V1\ThesisController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 routes — student mobile app (mounted at /api/v1)
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/semesters', [SemesterController::class, 'index']);
    Route::get('/semesters/active', [SemesterController::class, 'active']);

    Route::get('/enrollments', [EnrollmentController::class, 'index']);

    Route::get('/grades', [GradeController::class, 'index']);

    Route::get('/course-tree', [CourseTreeController::class, 'index']);

    Route::get('/surveys', [SurveyController::class, 'index']);
    Route::get('/surveys/available-courses', [SurveyController::class, 'availableCourses']);
    Route::post('/surveys', [SurveyController::class, 'store']);
    Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy']);

    Route::get('/add-drop-requests', [AddDropController::class, 'index']);
    Route::get('/add-drop-requests/options', [AddDropController::class, 'options']);
    Route::post('/add-drop-requests', [AddDropController::class, 'store']);

    Route::get('/suspension-requests', [SuspensionController::class, 'index']);
    Route::get('/suspension-requests/options', [SuspensionController::class, 'options']);
    Route::post('/suspension-requests', [SuspensionController::class, 'store']);

    Route::get('/thesis', [ThesisController::class, 'show']);
    Route::post('/thesis/register', [ThesisController::class, 'register']);

    Route::get('/exceptional-requests', [ExceptionalRequestController::class, 'index']);
    Route::get('/exceptional-requests/options', [ExceptionalRequestController::class, 'options']);
    Route::post('/exceptional-requests', [ExceptionalRequestController::class, 'store']);

    Route::get('/clearance', [ClearanceController::class, 'show']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});

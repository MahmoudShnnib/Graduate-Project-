<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return to_route('filament.admin.pages.dashboard');
});
Route::get('/apply', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/apply', [RegistrationController::class, 'store'])->name('registration.store');

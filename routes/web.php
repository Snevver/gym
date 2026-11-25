<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\GymLogController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TrainingSchemeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/inchecken', [AuthController::class, 'showLogin'])->middleware('throttle:10,60')->name('login');
Route::post('/inchecken/reset-password/request', [PasswordResetController::class, 'request'])->middleware('throttle:3,60')->name('password.reset.request');
Route::get('/inchecken/reset-password/{token}', [PasswordResetController::class, 'show'])->name('password.reset.form');
Route::post('/inchecken/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,60')->name('password.reset.update');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Gym log (everything requires login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::view('/', 'app', ['page' => 'GymLog', 'title' => 'Gym log'])->name('gym.log');
    Route::view('/calendar', 'app', ['page' => 'GymLog', 'title' => 'Gym log - Calendar'])->name('gym.log.calendar');
    Route::view('/scheme/{id}', 'app', ['page' => 'GymLog', 'title' => 'Gym log - Scheme'])->name('gym.log.scheme');

    Route::prefix('api')->group(function () {
        // Gym log
        Route::get('/gym-logs', [GymLogController::class, 'index'])->name('api.gym-logs.index');
        Route::get('/gym-logs/stats', [GymLogController::class, 'stats'])->name('api.gym-logs.stats');
        Route::post('/gym-logs/toggle', [GymLogController::class, 'toggle'])->name('api.gym-logs.toggle');

        // Training schemes
        Route::get('/training-schemes', [TrainingSchemeController::class, 'index'])->name('api.training-schemes.index');
        Route::post('/training-schemes', [TrainingSchemeController::class, 'store'])->name('api.training-schemes.store');
        Route::post('/training-schemes/reorder', [TrainingSchemeController::class, 'reorder'])->name('api.training-schemes.reorder');
        Route::post('/training-schemes/{scheme}', [TrainingSchemeController::class, 'update'])->name('api.training-schemes.update');
        Route::delete('/training-schemes/{scheme}', [TrainingSchemeController::class, 'destroy'])->name('api.training-schemes.destroy');
        Route::post('/training-schemes/{scheme}/exercises', [TrainingSchemeController::class, 'addExercise'])->name('api.training-schemes.exercises.add');
        Route::delete('/training-schemes/{scheme}/exercises/{exercise}', [TrainingSchemeController::class, 'removeExercise'])->name('api.training-schemes.exercises.remove');

        // Exercises
        Route::get('/exercises', [ExerciseController::class, 'index'])->name('api.exercises.index');
        Route::post('/exercises', [ExerciseController::class, 'store'])->name('api.exercises.store');
        Route::post('/exercises/{exercise}', [ExerciseController::class, 'update'])->name('api.exercises.update');
        Route::post('/exercises/{exercise}/weight', [ExerciseController::class, 'updateWeight'])->name('api.exercises.weight.update');
        Route::delete('/exercises/{exercise}', [ExerciseController::class, 'destroy'])->name('api.exercises.destroy');
    });
});

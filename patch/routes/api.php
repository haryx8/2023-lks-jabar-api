<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
Route::controller(AuthController::class)->group(function () {
    Route::post('auth/login', 'login');
    Route::post('auth/me', 'me');
    Route::post('auth/logout', 'logout');
    Route::post('auth/reset_password', 'reset_password');

    Route::post('register', 'register');
    Route::post('refresh', 'refresh');
});

use App\Http\Controllers\PollsController;
Route::controller(PollsController::class)->group(function () {
    Route::post('poll', 'poll');
    Route::get('poll', 'poll_get');
    Route::get('poll/{poll_id}', 'poll_get');
    Route::delete('poll/', 'poll_delete');
    Route::delete('poll/{poll_id}', 'poll_delete');
    Route::post('poll/{poll_id}/vote/{choice_id}', 'poll_vote');
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\AttendingGuestController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\KidsController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/invitation/{code}', [InvitationController::class, 'show']);
Route::post('/invitation/rsvp/{code}', [InvitationController::class, 'rsvp']);
Route::post('/invitations', [InvitationController::class, 'store']);
Route::post('/invitations/{code}', [InvitationController::class, 'update']);

Route::get('/pass/{code}', [InvitationController::class, 'showAttendingGuests']);

//Table Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/admin/seat-plan/guests', AttendingGuestController::class);
    Route::apiResource('/admin/seat-plan/tables', TableController::class);

    Route::apiResource('/admin/seat-plan/kids', KidsController::class);
});

Route::post('/login', [AuthenticationController::class, 'login']);

// Global Settings with middleware
Route::post('/rsvp/global_settings/lock', [InvitationController::class, 'lock']);
Route::get('/testing', [InvitationController::class, 'testing']);

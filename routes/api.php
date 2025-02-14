<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\AttendingGuestController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\KidsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSettingsController;
use App\Http\Controllers\RoleController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/invitation/{code}', [InvitationController::class, 'show']);
Route::post('/scan-qr/{code}', [InvitationController::class, 'scanQRCode']);
Route::post('/invitation/rsvp/{code}', [InvitationController::class, 'rsvp']);
Route::post('/invitations', [InvitationController::class, 'store']);
Route::post('/invitations/{code}', [InvitationController::class, 'update']);

Route::get('/pass/{code}', [InvitationController::class, 'showAttendingGuests']);

//Table Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/admin/dashboard', DashboardController::class);
    Route::apiResource('/admin/seat-plan/guests', AttendingGuestController::class);
    Route::apiResource('/admin/seat-plan/tables', TableController::class);
    Route::get('/admin/seat-plan/tables-guests', [TableController::class, 'tablesGuests']);
    Route::apiResource('/admin/seat-plan/kids', KidsController::class);

    Route::apiResource('/roles', RoleController::class);
});


Route::post('/login', [AuthenticationController::class, 'login']);

// Global Settings with middleware
Route::get('/server-date', [GlobalSettingsController::class, 'getServerDate']);
Route::get('/un-attend-all-expired-guests', [GlobalSettingsController::class, 'unAttendAllExpiredGuests']);
Route::post('/rsvp/global_settings/lock', [InvitationController::class, 'lock']);
Route::get('/testing', [InvitationController::class, 'testing']);

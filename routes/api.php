<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvitationController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/invite/{code}', [InvitationController::class, 'show']);
Route::post('/invite/rsvp/{code}', [InvitationController::class, 'rsvp']);
Route::post('/invitations', [InvitationController::class, 'store']);
Route::post('/invitations/{code}', [InvitationController::class, 'update']);
Route::post('/rsvp/global_settings/lock', [InvitationController::class, 'lock']);
// Route::get('/testing', [InvitationController::class, 'testing']);

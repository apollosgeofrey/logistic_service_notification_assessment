<?php

use App\Http\Controllers\API\NotificationController;
use Illuminate\Support\Facades\Route;

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
Route::prefix('v1')->group(function () {
    Route::post('/notifications/bulk-send', [NotificationController::class, 'bulkSend']);
    Route::get('/notifications/{id}', [NotificationController::class, 'notificationStatus']);
    Route::get('/subscribers/{id}/notifications', [NotificationController::class, 'subscriberNotifications']);
});

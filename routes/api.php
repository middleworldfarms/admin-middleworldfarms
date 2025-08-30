<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Conversation API routes - SECURED with ADMIN authentication only
Route::middleware('admin.auth')->prefix('conversations')->group(function () {
    Route::post('/', [ConversationController::class, 'store']);
    Route::get('/search', [ConversationController::class, 'search']);
    Route::get('/type/{type}', [ConversationController::class, 'byType']);
    Route::get('/user/{userId}', [ConversationController::class, 'userConversations']);
    Route::get('/{conversationId}', [ConversationController::class, 'show']);
});

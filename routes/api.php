<?php

use App\Http\Controllers\AIArtistControllers\JsonCompleteFileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIArtistControllers\StableDiffusionModelController;
use App\Http\Controllers\AIArtistControllers\JsonFileController;
use App\Http\Controllers\ObjectAi\FileUploadController;
use App\Http\Controllers\ObjectAi\VersionedJsonController;

// Object AI file upload endpoints 
Route::post('ObjectAiupdateModelJsonFile', [FileUploadController::class, 'ObjectAiupdateModelJsonFile']);
Route::post('GetobjectAiJsonFile', [FileUploadController::class, 'GetobjectAiJsonFile']);
// Object AI file upload endpoints for ios
Route::post('ObjectAiupdateModelJsonFileForIOS', [FileUploadController::class, 'ObjectAiupdateModelJsonFileForIOS']);
Route::post('GetobjectAiJsonFileForIOS', [FileUploadController::class, 'GetobjectAiJsonFileForIOS']);

// Object AI file upload endpoints for android 
Route::post('ObjectAiupdateModelJsonFileForAndroid', [FileUploadController::class, 'ObjectAiupdateModelJsonFileForAndroid']);
Route::post('GetobjectAiJsonFileForAndroid', [FileUploadController::class, 'GetobjectAiJsonFileForAndroid']);

// ─── Versioned Object AI APIs ───────────────────────────────
// Client APIs (app calls these with version)
Route::post('/object-ai/android', [VersionedJsonController::class, 'getAndroidJson']);
Route::post('/object-ai/ios', [VersionedJsonController::class, 'getIosJson']);
Route::post('/object-ai/debug', [VersionedJsonController::class, 'getDebugJson']);

// Admin APIs (manage versioned JSON files)
Route::post('/object-ai/versions/list', [VersionedJsonController::class, 'listVersions']);
Route::post('/object-ai/versions/create', [VersionedJsonController::class, 'createVersion']);
Route::post('/object-ai/versions/update', [VersionedJsonController::class, 'updateVersion']);
Route::post('/object-ai/versions/delete', [VersionedJsonController::class, 'deleteVersion']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\BulkDidApiController;
use App\Http\Controllers\Api\AbuseDidApiController;

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

Route::get('/status', [StatusController::class, 'index']);

// Bulk DID Test Result APIs
Route::match(['get', 'post'], '/bulk-did/check', [BulkDidApiController::class, 'check'])->name('api.bulk-did.check');
Route::get('/bulk-did/status/{did}', [BulkDidApiController::class, 'getStatusByParam'])->name('api.bulk-did.status');
Route::post('/bulk-did/batch-check', [BulkDidApiController::class, 'batchCheck'])->name('api.bulk-did.batch-check');
Route::get('/bulk-did/list', [BulkDidApiController::class, 'listAll'])->name('api.bulk-did.list');

// Abuse DID Status Check APIs
Route::match(['get', 'post'], '/abuse-did/check', [AbuseDidApiController::class, 'check'])->name('api.abuse-did.check');
Route::get('/abuse-did/status/{did}', [AbuseDidApiController::class, 'getStatusByParam'])->name('api.abuse-did.status');
Route::post('/abuse-did/batch-check', [AbuseDidApiController::class, 'batchCheck'])->name('api.abuse-did.batch-check');
Route::get('/abuse-did/list', [AbuseDidApiController::class, 'listAll'])->name('api.abuse-did.list');


<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AprioriReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Apriori Recommendation Report APIs
Route::middleware('auth:sanctum')->prefix('apriori')->group(function () {
    Route::get('stats', [AprioriReportController::class, 'stats']);
    Route::get('rules', [AprioriReportController::class, 'rules']);
    Route::get('itemsets', [AprioriReportController::class, 'itemsets']);
    Route::post('cache/clear', [AprioriReportController::class, 'clearCache']);
});

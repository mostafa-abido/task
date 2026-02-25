<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('contracts/{contract}/invoices', [InvoiceController::class, 'store']);
    Route::get('contracts/{contract}/invoices', [InvoiceController::class, 'index']);
    Route::get('contracts/{contract}/summary', [InvoiceController::class, 'summary']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment']);
});

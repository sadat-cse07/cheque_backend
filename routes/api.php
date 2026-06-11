<?php

use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TemplateDocumentController;
use App\Http\Controllers\Api\TemplateLetterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\BankController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\ChequeController;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

// Vendors
Route::apiResource('vendors', VendorController::class);

// Banks
Route::apiResource('banks', BankController::class);

// Vouchers
Route::apiResource('vouchers', VoucherController::class);
Route::get('vouchers/unpaid/by-vendor', [VoucherController::class, 'unpaidByVendor']);

// Cheques
Route::get('cheques', [ChequeController::class, 'index']);
Route::post('cheques', [ChequeController::class, 'store']);
Route::get('cheques/stats', [ChequeController::class, 'stats']);
Route::get('cheques/{cheque}', [ChequeController::class, 'show']);
Route::delete('cheques/{cheque}', [ChequeController::class, 'destroy']);
Route::get('cheques/{cheque}/print', [ChequeController::class, 'printData']);
Route::match(['get', 'post'], 'cheques/bulk/print', [ChequeController::class, 'bulkPrintData']);

// Templates
Route::apiResource('templates', TemplateController::class);
Route::get('templates/{template}/placeholders', [TemplateController::class, 'placeholders']);

// Template Documents
Route::apiResource('template-documents', TemplateDocumentController::class);
Route::post('template-documents/{document}/generate', [TemplateDocumentController::class, 'generate']);
Route::get('template-documents/{document}/print', [TemplateDocumentController::class, 'printData']);
Route::post('template-documents/bulk-print', [TemplateDocumentController::class, 'bulkPrint']);

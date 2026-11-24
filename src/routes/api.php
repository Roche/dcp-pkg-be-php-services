<?php

use Illuminate\Support\Facades\Route;
use Dcp\Http\Controllers\VersionController;
use Dcp\Http\Controllers\AuditLogController;

$apiVersion = config('dcp.api_version_prefix');

Route::prefix($apiVersion)->group(function () {
    Route::get('Version/GetBEVersion', [VersionController::class, 'getVersion']);

    Route::middleware(['dcp.auth'])->group(function () {
        Route::get('/AuditTrail/GetModuleAuditTrail', [AuditLogController::class, 'getAuditTrail']);
    });
});

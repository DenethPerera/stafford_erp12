<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('labs', \App\Http\Controllers\LabController::class);
        
        Route::resource('roles', \App\Http\Controllers\RoleController::class);
        Route::get('permissions/grid', [\App\Http\Controllers\RoleController::class, 'grid'])->name('permissions.grid');
        Route::post('permissions/assign', [\App\Http\Controllers\RoleController::class, 'assignPrivileges'])->name('permissions.assign');
        Route::post('permissions/revoke', [\App\Http\Controllers\RoleController::class, 'revokePrivileges'])->name('permissions.revoke');
        Route::resource('cpsc-tokens', \App\Http\Controllers\CpscTokenController::class)->only(['index', 'store', 'destroy']);
        
        Route::get('activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::resource('standards', \App\Http\Controllers\StandardController::class);
        Route::resource('standard-stages', \App\Http\Controllers\StandardStageController::class);
        
        Route::resource('certificates', \App\Http\Controllers\CertificateController::class)->except(['index']);
        Route::post('certificates/{certificate}/renewals', [\App\Http\Controllers\RenewalProcessController::class, 'store'])->name('renewals.store');
        Route::post('renewals/{renewal}/stage', [\App\Http\Controllers\RenewalProcessController::class, 'updateStage'])->name('renewals.update_stage');
        Route::post('standard-stages/reorder', [\App\Http\Controllers\StandardStageController::class, 'reorder'])
        ->name('standard-stages.reorder');

        Route::get('rfid-generation', [\App\Http\Controllers\RfidGenerationController::class, 'index'])->name('rfid-generation.index');
        Route::get('rfid-generation/export', [\App\Http\Controllers\RfidGenerationController::class, 'export'])->name('rfid-generation.export');

        Route::prefix('master-data')->name('master-data.')->group(function () {
            Route::resource('seasons', \App\Http\Controllers\SeasonController::class);
        });
    });
    
    
    // Production Orders redirect to Order Allocations for Admin
    Route::middleware('role:super_admin|factory')->group(function () {
        Route::get('prod-orders', fn() => redirect()->route('order-allocations.index'))->name('prod-orders.index');
        Route::get('prod-orders/data', [\App\Http\Controllers\ProdOrderController::class, 'data'])->name('prod-orders.data');
        Route::get('prod-orders/{id}', [\App\Http\Controllers\ProdOrderController::class, 'show'])->name('prod-orders.show');
        Route::post('prod-orders/{id}/create-po', [\App\Http\Controllers\ProdOrderController::class, 'createPurchaseOrder'])->name('prod-orders.create-po');
        Route::post('prod-orders/{id}/create-cert', [\App\Http\Controllers\ProdOrderController::class, 'createCertificate'])->name('prod-orders.create-cert');
    });

    // Routes shared between super_admin and lab
    Route::middleware('role:super_admin|lab')->group(function () {
        Route::resource('factories', \App\Http\Controllers\FactoryController::class);
        
        // Stafford CRUD Routes
        Route::get('stafford-certs/{stafford_cert}/export-pdf', [\App\Http\Controllers\StaffordQualificationController::class, 'exportPdf'])->name('stafford-certs.export-pdf');
        Route::post('stafford-certs/export-bulk-pdf', [\App\Http\Controllers\StaffordQualificationController::class, 'exportBulkPdf'])->name('stafford-certs.export-bulk-pdf');
        Route::post('stafford-certs/bulk-action', [\App\Http\Controllers\StaffordQualificationController::class, 'bulkAction'])->name('stafford-certs.bulk-action');
        Route::get('stafford-certs/erp-po-lookup', [\App\Http\Controllers\StaffordQualificationController::class, 'eravPoLookup'])->name('stafford-certs.erp-po-lookup');
        Route::get('stafford-certs/erp-po-search', [\App\Http\Controllers\StaffordQualificationController::class, 'eravPoSearch'])->name('stafford-certs.erp-po-search');
        Route::resource('stafford-certs', \App\Http\Controllers\StaffordQualificationController::class)
            ->except(['create', 'edit'])
            ->names([
                'index' => 'stafford-certs.index',
                'store' => 'stafford-certs.store',
                'show' => 'stafford-certs.show',
                'update' => 'stafford-certs.update',
                'destroy' => 'stafford-certs.destroy',
            ]);
        // Test Report Types CRUD
        Route::resource('test-report-types', \App\Http\Controllers\TestReportTypeController::class);
    });

    // Laboratory specific routes
    Route::middleware('role:lab')->prefix('lab')->name('lab.')->group(function () {
        Route::get('pos', [\App\Http\Controllers\LabPoController::class, 'index'])->name('pos.index');
        Route::get('pos/data', [\App\Http\Controllers\LabPoController::class, 'poData'])->name('pos.data');
        Route::get('completed-tasks', [\App\Http\Controllers\LabPoController::class, 'completedTasks'])->name('tasks.completed');
        Route::put('tasks/{id}', [\App\Http\Controllers\LabPoController::class, 'updateTask'])->name('tasks.update');
        Route::post('upload-report-file', [\App\Http\Controllers\LabPoController::class, 'uploadReportFile'])->name('upload-report-file');
    });

    // Stafford Order Allocations (super_admin)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('order-allocations', [\App\Http\Controllers\OrderAllocationController::class, 'index'])->name('order-allocations.index');
    });

    // Factory Portal (accessible to factory and super_admin)
    Route::middleware('role:factory|super_admin')->group(function () {
        Route::get('factory-portal', [\App\Http\Controllers\FactoryPortalController::class, 'index'])->name('factory-portal.index');
        Route::get('factory-portal/{id}', [\App\Http\Controllers\FactoryPortalController::class, 'show'])->name('factory-portal.show');
        Route::post('factory-portal/{id}/store-po', [\App\Http\Controllers\FactoryPortalController::class, 'storePurchaseOrder'])->name('factory-portal.store-po');

        // Purchase Orders
        Route::get('purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/{id}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    });
});

require __DIR__.'/auth.php';


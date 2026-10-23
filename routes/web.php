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
    
    
    // Production Orders redirect based on role
    Route::middleware('role:super_admin|factory')->group(function () {
        Route::get('prod-orders', function () {
            return auth()->user()?->isFactory() 
                ? redirect()->route('factory-portal.index')
                : redirect()->route('purchase-orders.index');
        })->name('prod-orders.index');
    });

    // Routes shared between super_admin and lab
    Route::middleware('role:super_admin|lab')->group(function () {
        Route::resource('factories', \App\Http\Controllers\FactoryController::class);
        
        // Stafford CRUD Routes
        Route::get('stafford-certs/{stafford_cert}/export-pdf', [\App\Http\Controllers\StaffordQualificationController::class, 'exportPdf'])->name('stafford-certs.export-pdf');
        Route::post('stafford-certs/export-bulk-pdf', [\App\Http\Controllers\StaffordQualificationController::class, 'exportBulkPdf'])->name('stafford-certs.export-bulk-pdf');
        Route::post('stafford-certs/bulk-action', [\App\Http\Controllers\StaffordQualificationController::class, 'bulkAction'])->name('stafford-certs.bulk-action');
        Route::post('stafford-certs/{stafford_cert}/reject', [\App\Http\Controllers\StaffordQualificationController::class, 'reject'])->name('stafford-certs.reject');
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
        // Upload report file (accessible by both super_admin and lab)
        Route::post('stafford-certs/upload-report-file', [\App\Http\Controllers\StaffordQualificationController::class, 'uploadReportFile'])->name('stafford-certs.upload-report-file');
    });

    // Laboratory specific routes
    Route::middleware('role:lab')->prefix('lab')->name('lab.')->group(function () {
        Route::get('pos', [\App\Http\Controllers\LabPoController::class, 'index'])->name('pos.index');
        Route::get('pos/data', [\App\Http\Controllers\LabPoController::class, 'poData'])->name('pos.data');
        Route::get('completed-tasks', [\App\Http\Controllers\LabPoController::class, 'completedTasks'])->name('tasks.completed');
        Route::put('tasks/{id}', [\App\Http\Controllers\LabPoController::class, 'updateTask'])->name('tasks.update');
        Route::post('upload-report-file', [\App\Http\Controllers\LabPoController::class, 'uploadReportFile'])->name('upload-report-file');
    });

    // Stafford Order Allocations
    Route::middleware('role:super_admin')->group(function () {
        Route::get('order-allocations', function () {
            return redirect()->route('purchase-orders.index');
        })->name('order-allocations.index');
    });

    // Factory Portal - PO creation and factory dashboard 
    Route::middleware('role:factory')->group(function () {
        Route::get('factory-portal', [\App\Http\Controllers\FactoryPortalController::class, 'index'])->name('factory-portal.index');
        Route::post('factory-portal/{id}/store-po', [\App\Http\Controllers\FactoryPortalController::class, 'storePurchaseOrder'])->name('factory-portal.store-po');
    });

    // View-Only for Orders & Purchase Orders
    Route::middleware('role:factory|super_admin')->group(function () {
        Route::get('factory-portal/{id}', [\App\Http\Controllers\FactoryPortalController::class, 'show'])->name('factory-portal.show');

        // Purchase Orders
        Route::get('purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/{id}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    });
});

// Fallback route to safely stream storage files if symlink is unavailable
Route::get('storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    return response()->file($filePath);
})->where('path', '.*');

require __DIR__.'/auth.php';


<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ForwarderLoginController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');


    Route::middleware('role:super_admin')->group(function () {
        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::resource('labs', \App\Http\Controllers\LabController::class);
// custom actions
        Route::get('shipment-management/purchase-orders',[ \App\Http\Controllers\ShipmentManagementController::class,'purchaseOrdersByBuyer'])->name('shipment-management.purchase-orders');
        Route::get('shipment-management/scan', [\App\Http\Controllers\ShipmentManagementController::class, 'scan'])->name('shipment-management.scan');
        Route::get('shipment-management/documents/{shipmentDocument}', [\App\Http\Controllers\ShipmentDocumentController::class, 'document'])->name('shipment-management.documents.show');
        Route::get('shipment-management/forwarder-login', [\App\Http\Controllers\ShipmentManagementController::class, 'forwarderLogin'])->name('shipment-management.forwarder-login');
        Route::resource('shipment-management',\App\Http\Controllers\ShipmentManagementController::class);

        Route::post('shipment-management/scan/link-import', [
    \App\Http\Controllers\ShipmentManagementController::class,
    'saveShipmentImport',
])->name('shipment-management.save-import');

Route::resource(
    'forwarders',
    \App\Http\Controllers\ForwarderController::class
)->only(['index', 'store', 'destroy']);

Route::get(
    'forwarders/{forwarder}/documents/{document}',
    [\App\Http\Controllers\ForwarderController::class, 'document']
)->name('forwarders.document');

Route::delete(
    'forwarder-documents/{forwarderDocument}',
    [\App\Http\Controllers\ForwarderController::class, 'destroyDocuments']
)->name('forwarder-documents.destroy');




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
            Route::resource('countries', \App\Http\Controllers\CountryController::class)->only(['index', 'store']);
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
        Route::get('stafford-certs/report-file', [\App\Http\Controllers\StaffordQualificationController::class, 'viewReportFile'])->name('stafford-certs.view-report-file');
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
        Route::get('shipment-documents', [\App\Http\Controllers\ShipmentDocumentController::class, 'index'])->name('shipment-documents.index');
        Route::post('shipment-documents', [\App\Http\Controllers\ShipmentDocumentController::class, 'store'])->name('shipment-documents.store');
        Route::delete('shipment-documents/{shipmentDocument}', [\App\Http\Controllers\ShipmentDocumentController::class, 'destroy'])->name('shipment-documents.destroy');
        Route::get('shipment-documents/{shipmentDocument}/document', [\App\Http\Controllers\ShipmentDocumentController::class, 'document'])->name('shipment-documents.document');

        Route::get('factory-portal', [\App\Http\Controllers\FactoryPortalController::class, 'index'])->name('factory-portal.index');
        Route::post('factory-portal/{id}/store-po', [\App\Http\Controllers\FactoryPortalController::class, 'storePurchaseOrder'])->name('factory-portal.store-po');

        Route::view('import-export', 'factory_portal.import_export')->name('import-export.index');

        Route::get('shipment-details',[\App\Http\Controllers\FactoryPortalController::class, 'shipmentDetails'])->name('shipment-details.index');
        Route::get('create-shipment', [\App\Http\Controllers\FactoryPortalController::class, 'createShipment'])->name('create-shipment.index');
        Route::get('create-shipment/history', [\App\Http\Controllers\FactoryPortalController::class, 'shipmentHistory'])->name('create-shipment.history');
        Route::get('create-shipment/{id}/edit', [\App\Http\Controllers\FactoryPortalController::class, 'editShipment'])->name('create-shipment.edit');
        Route::put('create-shipment/{id}', [\App\Http\Controllers\FactoryPortalController::class, 'updateShipment'])->name('create-shipment.update');
        Route::delete('create-shipment/{id}', [\App\Http\Controllers\FactoryPortalController::class, 'destroyShipment'])->name('create-shipment.destroy');
        Route::post('create-shipment', [\App\Http\Controllers\FactoryPortalController::class, 'storeShipment'])->name('create-shipment.store');
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
    $cleanPath = str_replace(['..', '\\'], ['', '/'], $path);
    $cleanPath = ltrim($cleanPath, '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/lab_reports/' . basename($cleanPath)),
        storage_path('app/' . $cleanPath),
        public_path('storage/' . $cleanPath),
    ];

    $filePath = null;
    foreach ($candidates as $candidate) {
        if (file_exists($candidate) && is_file($candidate)) {
            $filePath = $candidate;
            break;
        }
    }

    if (!$filePath) {
        abort(404, 'File not found in storage: ' . htmlspecialchars(basename($cleanPath)));
    }

    $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
    ]);
})->where('path', '.*');



Route::prefix('forwarder')->name('forwarder.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [ForwarderLoginController::class, 'create'])
            ->name('login');

        Route::post('/login', [ForwarderLoginController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('login.store');
    });

    Route::middleware(['auth', 'role:forwarder'])->group(function () {
        Route::view('/dashboard', 'forwarders.dashboard')
            ->name('dashboard');

        Route::get('/file-upload', [\App\Http\Controllers\ForwarderController::class, 'fileUpload'])
            ->name('file-upload');

        Route::post('/file-upload', [\App\Http\Controllers\ForwarderController::class, 'storeDocuments'])
            ->name('file-upload.store');

        Route::get('/documents/{forwarder}/{document}', [\App\Http\Controllers\ForwarderController::class, 'document'])
            ->name('documents.show');

        Route::delete('/documents/{forwarderDocument}', [\App\Http\Controllers\ForwarderController::class, 'destroyDocuments'])
            ->name('documents.destroy');

        Route::post('/logout', [ForwarderLoginController::class, 'destroy'])
            ->name('logout');
    });
});



require __DIR__.'/auth.php';


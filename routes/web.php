<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CuttingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\OperationalKanbanController;
use App\Http\Controllers\RejectController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\MasterDataImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\ProductMasterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleAccessController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\StockCardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
    Route::middleware('role:super_admin')->group(function (): void {
        Route::get('/knowledge-base/{article}/edit', [KnowledgeBaseController::class, 'edit'])->name('knowledge-base.edit');
        Route::put('/knowledge-base/{article}', [KnowledgeBaseController::class, 'update'])->name('knowledge-base.update');
    });
    Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::middleware('permission:delivery_orders.view')->group(function (): void {
        Route::get('/delivery-orders', [DeliveryOrderController::class, 'index'])->name('delivery-orders.index');
    });
    Route::middleware('permission:delivery_orders.manage')->group(function (): void {
        Route::get('/delivery-orders/create', [DeliveryOrderController::class, 'create'])->name('delivery-orders.create');
        Route::get('/delivery-orders/purchase-orders/{poNumber}', [DeliveryOrderController::class, 'purchaseOrderItems'])
            ->name('delivery-orders.purchase-orders');
        Route::post('/delivery-orders', [DeliveryOrderController::class, 'store'])->name('delivery-orders.store');
    });
    Route::middleware('permission:goods_receipts.view')->group(function (): void {
        Route::get('/goods-receipts', [DeliveryOrderController::class, 'receipts'])->name('goods-receipts.index');
    });
    Route::middleware('permission:goods_receipts.manage')->post('/goods-receipts', [DeliveryOrderController::class, 'storeReceipt'])->name('goods-receipts.store');
    Route::middleware('permission:barcodes')->group(function (): void {
        Route::get('/barcodes', [ProductMasterController::class, 'barcodes'])->name('barcodes.index');
    });
    Route::middleware('permission:reports')->group(function (): void {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('/reports/export/excel', [ReportController::class, 'excel'])->name('reports.excel');
    });
    Route::middleware('permission:stock_cards')->group(function (): void {
        Route::get('/stock-cards', [StockCardController::class, 'index'])->name('stock-cards.index');
        Route::post('/stock-cards', [StockCardController::class, 'store'])->name('stock-cards.store');
    });
    Route::middleware('permission:cutting')->get('/cutting', [CuttingController::class, 'index'])->name('cutting.index');
    Route::middleware('permission:rejects.view')->get('/rejects', RejectController::class)->name('rejects.index');
    Route::middleware('permission:spk.view')->prefix('spk')->name('spk.')->group(function (): void {
        Route::get('/', [SpkController::class, 'index'])->name('index');
        Route::get('/{group}', [SpkController::class, 'show'])->name('show');
        Route::get('/{group}/pdf', [SpkController::class, 'pdf'])->name('pdf');
        Route::post('/{group}/sign', [SpkController::class, 'sign'])->name('sign');
    });
    Route::middleware('permission:orders.view')->get('/orders', [ProductionOrderController::class, 'index'])->name('orders.index');
    Route::middleware('permission:orders.view')->get('/operational-kanban', OperationalKanbanController::class)->name('orders.kanban');
    Route::get('/orders/create', [ProductionOrderController::class, 'create'])
        ->middleware('permission:orders.create')
        ->name('orders.create');
    Route::post('/orders', [ProductionOrderController::class, 'store'])
        ->middleware('permission:orders.create')
        ->name('orders.store');
    Route::middleware('permission:orders.view')->group(function (): void {
        Route::get('/orders/{order}/pdf', [ProductionOrderController::class, 'pdf'])->name('orders.pdf');
        Route::get('/orders/{order}', [ProductionOrderController::class, 'show'])->name('orders.show');
    });
    Route::post('/orders/{order}/actions', [ProductionOrderController::class, 'storeAction'])
        ->middleware('permission:orders.view')
        ->name('orders.actions.store');
    Route::middleware('permission:manage.master_data')->prefix('master-data')->name('master-data.')->group(function (): void {
        Route::get('/import/template', [MasterDataImportController::class, 'template'])->name('import.template');
        Route::get('/import', [MasterDataImportController::class, 'index'])->name('import');
        Route::post('/import', [MasterDataImportController::class, 'store'])->name('import.store');
        Route::get('/products', [ProductMasterController::class, 'products'])->name('products');
        Route::get('/products/create', [ProductMasterController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [ProductMasterController::class, 'storeProduct'])->name('products.store');
        Route::get('/ratios', [ProductMasterController::class, 'ratios'])->name('ratios');
        Route::post('/ratios', [ProductMasterController::class, 'storeRatio'])->name('ratios.store');
        Route::post('/ratios/{ratioId}', [ProductMasterController::class, 'updateRatio'])->name('ratios.update');
        Route::get('/{type}/create', [ProductMasterController::class, 'create'])->name('create');
        Route::post('/{type}', [ProductMasterController::class, 'store'])->name('store');
        Route::get('/{type}', [ProductMasterController::class, 'index'])->name('index');
    });
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::middleware('role:super_admin')->group(function (): void {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings/truncate', [SettingsController::class, 'truncate'])->name('settings.truncate');
        });
        Route::middleware('permission:manage.roles')->group(function (): void {
            Route::get('/role-access', [RoleAccessController::class, 'index'])->name('role-access.index');
            Route::put('/role-access', [RoleAccessController::class, 'update'])->name('role-access.update');
        });
        Route::middleware('permission:manage.users')->group(function (): void {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        });
        Route::middleware('permission:manage.branches')->group(function (): void {
            Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
            Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
            Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
            Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
        });
        Route::middleware('permission:manage.backup')->group(function (): void {
            Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
            Route::post('/backup', [BackupController::class, 'create'])->name('backup.create');
            Route::get('/backup/{filename}', [BackupController::class, 'download'])->name('backup.download');
        });
        Route::middleware('permission:manage.audit_logs')->group(function (): void {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });
    });
});

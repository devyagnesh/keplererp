<?php

use App\Http\Controllers\Admin\AllowanceTypeController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BatchSerialTraceabilityController;
use App\Http\Controllers\Admin\BillOfMaterialController;
use App\Http\Controllers\Admin\ChartOfAccountsController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Http\Controllers\Admin\CustomerAddressController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DesignationController;
use App\Http\Controllers\Admin\DispatchChallanController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\FinancePaymentController;
use App\Http\Controllers\Admin\GoodsReceiptController;
use App\Http\Controllers\Admin\GrnReturnController;
use App\Http\Controllers\Admin\InventoryBalanceController;
use App\Http\Controllers\Admin\InventoryStockController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ItemTrackingController;
use App\Http\Controllers\Admin\JournalVoucherController;
use App\Http\Controllers\Admin\LeaveApplicationController;
use App\Http\Controllers\Admin\PayrollDetailController;
use App\Http\Controllers\Admin\PayrollRunController;
use App\Http\Controllers\Admin\PayrollSettingsController;
use App\Http\Controllers\Admin\PriceListController;
use App\Http\Controllers\Admin\ProductionOrderController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseRequisitionController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SalesEnquiryController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\SalesQuotationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorInvoiceController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\Employee\EmployeeAttendanceController;
use App\Http\Controllers\Employee\EmployeeDashboardController;
use App\Http\Controllers\Employee\EmployeeLeaveController;
use App\Http\Controllers\Employee\EmployeePayslipController;
use App\Http\Controllers\Employee\EmployeeProfileController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\VendorPortal\PortalAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');

Route::get('/documents/{generatedDocument}/download', DocumentDownloadController::class)
    ->name('documents.download');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.home')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/admin', function () {
        $user = auth()->user();
        if ($user === null) {
            return redirect()->route('login');
        }
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->can('company.view')) {
            return redirect()->route('admin.company.edit');
        }
        if ($user->can('vendors.view')) {
            return redirect()->route('admin.vendors.index');
        }
        if ($user->can('customers.view')) {
            return redirect()->route('admin.customers.index');
        }
        if ($user->can('inventory.view')) {
            return redirect()->route('admin.warehouses.index');
        }
        if ($user->can('purchase.pr.create')) {
            return redirect()->route('admin.purchase.requisitions.index');
        }
        if ($user->can('purchase.po.create') || $user->can('purchase.po.approve')) {
            return redirect()->route('admin.purchase.orders.index');
        }
        if ($user->can('sales.quotation.create')) {
            return redirect()->route('admin.sales.quotations.index');
        }
        if ($user->can('sales.order.create') || $user->can('sales.dispatch')) {
            return redirect()->route('admin.sales.orders.index');
        }
        if ($user->can('production.bom.create')) {
            return redirect()->route('admin.production.boms.index');
        }
        if ($user->can('production.order.create') || $user->can('production.log')) {
            return redirect()->route('admin.production.work-orders.index');
        }
        if ($user->can('finance.voucher.create') || $user->can('finance.reports.view')) {
            return redirect()->route('admin.finance.vouchers.index');
        }
        if ($user->can('hr.employee.manage')) {
            return redirect()->route('admin.hr.employees.index');
        }
        if ($user->can('hr.attendance.mark')) {
            return redirect()->route('admin.hr.attendance.index');
        }
        if ($user->can('hr.payroll.run')) {
            return redirect()->route('admin.hr.payroll-runs.index');
        }
        if ($user->can('hr.payslip.view')) {
            return redirect()->route('employee.dashboard');
        }
        if ($user->can('reports.sales') || $user->can('reports.purchase') || $user->can('reports.inventory') || $user->can('reports.finance') || $user->can('hr.employee.manage')) {
            return redirect()->route('admin.reports.index');
        }
        if ($user->can('users.view')) {
            return redirect()->route('admin.users.index');
        }
        abort(403);
    })->name('admin.home');

    Route::get('/admin/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:Super Admin|Admin')
        ->name('admin.dashboard');
    Route::view('/admin/license-expired', 'admin.license.expired')->name('admin.license.expired');

    Route::middleware('permission:company.view')->group(function (): void {
        Route::get('/admin/company', [CompanyController::class, 'edit'])->name('admin.company.edit');
    });
    Route::middleware('permission:company.edit')->group(function (): void {
        Route::put('/admin/company', [CompanyController::class, 'update'])->name('admin.company.update');
    });

    Route::middleware('permission:users.view')->group(function (): void {
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users/data', [UserController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.users.data');
    });

    Route::middleware('permission:users.create')->group(function (): void {
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    });

    Route::middleware('permission:users.edit')->group(function (): void {
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    });

    Route::middleware('permission:users.delete')->group(function (): void {
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.users.destroy');
    });

    Route::middleware('permission:vendors.view')->group(function (): void {
        Route::get('/admin/vendors', [VendorController::class, 'index'])->name('admin.vendors.index');
        Route::post('/admin/vendors/data', [VendorController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.vendors.data');
    });

    Route::middleware('permission:vendors.create')->group(function (): void {
        Route::get('/admin/vendors/create', [VendorController::class, 'create'])->name('admin.vendors.create');
        Route::post('/admin/vendors', [VendorController::class, 'store'])->name('admin.vendors.store');
    });

    Route::middleware('permission:vendors.edit')->group(function (): void {
        Route::get('/admin/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('admin.vendors.edit');
        Route::put('/admin/vendors/{vendor}', [VendorController::class, 'update'])->name('admin.vendors.update');
        Route::post('/admin/vendors/{vendor}/documents', [VendorController::class, 'storeDocument'])
            ->name('admin.vendors.documents.store');
        Route::delete('/admin/vendors/{vendor}/documents/{vendorDocument}', [VendorController::class, 'destroyDocument'])
            ->name('admin.vendors.documents.destroy');
        Route::post('/admin/vendors/{vendor}/block', [VendorController::class, 'block'])->name('admin.vendors.block');
        Route::post('/admin/vendors/{vendor}/activate', [VendorController::class, 'activate'])->name('admin.vendors.activate');
    });

    Route::middleware('permission:vendors.delete')->group(function (): void {
        Route::delete('/admin/vendors/{vendor}', [VendorController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.vendors.destroy');
    });

    Route::middleware('permission:vendors.approve')->group(function (): void {
        Route::post('/admin/vendors/{vendor}/approve', [VendorController::class, 'approve'])
            ->middleware('throttle:120,1')
            ->name('admin.vendors.approve');
    });

    Route::middleware('permission:customers.view')->group(function (): void {
        Route::get('/admin/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
        Route::post('/admin/customers/data', [CustomerController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.customers.data');
    });

    Route::middleware('permission:customers.create')->group(function (): void {
        Route::get('/admin/customers/create', [CustomerController::class, 'create'])->name('admin.customers.create');
        Route::post('/admin/customers', [CustomerController::class, 'store'])->name('admin.customers.store');
    });

    Route::middleware('permission:customers.edit')->group(function (): void {
        Route::get('/admin/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('admin.customers.edit');
        Route::put('/admin/customers/{customer}', [CustomerController::class, 'update'])->name('admin.customers.update');
        Route::get('/admin/customers/{customer}/addresses', [CustomerAddressController::class, 'index'])->name('admin.customers.addresses.index');
        Route::post('/admin/customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('admin.customers.addresses.store');
        Route::post('/admin/customers/{customer}/block', [CustomerController::class, 'block'])->name('admin.customers.block');
        Route::post('/admin/customers/{customer}/activate', [CustomerController::class, 'activate'])->name('admin.customers.activate');
    });

    Route::middleware('permission:customers.delete')->group(function (): void {
        Route::delete('/admin/customers/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.customers.destroy');
    });

    Route::middleware('permission:inventory.view')->group(function (): void {
        Route::get('/admin/warehouses', [WarehouseController::class, 'index'])->name('admin.warehouses.index');
        Route::post('/admin/warehouses/data', [WarehouseController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.warehouses.data');
        Route::get('/admin/items', [ItemController::class, 'index'])->name('admin.items.index');
        Route::post('/admin/items/data', [ItemController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.items.data');
        Route::get('/admin/inventory/balances', [InventoryBalanceController::class, 'index'])->name('admin.inventory.balances.index');
        Route::post('/admin/inventory/balances/data', [InventoryBalanceController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.balances.data');
        Route::get('/admin/inventory/tracking-map', [ItemTrackingController::class, 'trackingMap'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.tracking-map');
        Route::get('/admin/inventory/warehouses/{warehouse}/items/{item}/batches', [ItemTrackingController::class, 'batches'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.item-batches');
        Route::get('/admin/inventory/warehouses/{warehouse}/items/{item}/serials', [ItemTrackingController::class, 'serials'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.item-serials');
    });

    Route::middleware('permission:reports.inventory')->group(function (): void {
        Route::get('/admin/inventory/traceability', [BatchSerialTraceabilityController::class, 'index'])
            ->name('admin.inventory.traceability.index');
        Route::post('/admin/inventory/traceability/fefo-data', [BatchSerialTraceabilityController::class, 'fefoData'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.traceability.fefo-data');
        Route::post('/admin/inventory/traceability/expiry-data', [BatchSerialTraceabilityController::class, 'expiryData'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.traceability.expiry-data');
        Route::post('/admin/inventory/traceability/history-data', [BatchSerialTraceabilityController::class, 'historyData'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.traceability.history-data');
        Route::get('/admin/inventory/traceability/export/fefo', [BatchSerialTraceabilityController::class, 'exportFefoCsv'])
            ->name('admin.inventory.traceability.export-fefo');
        Route::get('/admin/inventory/traceability/export/history', [BatchSerialTraceabilityController::class, 'exportHistoryCsv'])
            ->name('admin.inventory.traceability.export-history');
    });

    Route::middleware('permission:inventory.adjust')->group(function (): void {
        Route::get('/admin/warehouses/create', [WarehouseController::class, 'create'])->name('admin.warehouses.create');
        Route::post('/admin/warehouses', [WarehouseController::class, 'store'])->name('admin.warehouses.store');
        Route::get('/admin/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('admin.warehouses.edit');
        Route::put('/admin/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('admin.warehouses.update');
        Route::delete('/admin/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.warehouses.destroy');
        Route::get('/admin/items/create', [ItemController::class, 'create'])->name('admin.items.create');
        Route::post('/admin/items', [ItemController::class, 'store'])->name('admin.items.store');
        Route::get('/admin/items/{item}/edit', [ItemController::class, 'edit'])->name('admin.items.edit');
        Route::put('/admin/items/{item}', [ItemController::class, 'update'])->name('admin.items.update');
        Route::delete('/admin/items/{item}', [ItemController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.items.destroy');
        Route::get('/admin/inventory/adjust', [InventoryStockController::class, 'adjustForm'])->name('admin.inventory.adjust.form');
        Route::post('/admin/inventory/adjust', [InventoryStockController::class, 'adjust'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.adjust');
    });

    Route::middleware('permission:inventory.transfer')->group(function (): void {
        Route::get('/admin/inventory/transfer', [InventoryStockController::class, 'transferForm'])->name('admin.inventory.transfer.form');
        Route::post('/admin/inventory/transfer', [InventoryStockController::class, 'transfer'])
            ->middleware('throttle:120,1')
            ->name('admin.inventory.transfer');
    });

    Route::middleware('permission:purchase.pr.create')->group(function (): void {
        Route::get('/admin/purchase/requisitions', [PurchaseRequisitionController::class, 'index'])->name('admin.purchase.requisitions.index');
        Route::post('/admin/purchase/requisitions/data', [PurchaseRequisitionController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.requisitions.data');
        Route::get('/admin/purchase/requisitions/create', [PurchaseRequisitionController::class, 'create'])->name('admin.purchase.requisitions.create');
        Route::post('/admin/purchase/requisitions', [PurchaseRequisitionController::class, 'store'])->name('admin.purchase.requisitions.store');
        Route::delete('/admin/purchase/requisitions/{purchase_requisition}', [PurchaseRequisitionController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.requisitions.destroy');
        Route::post('/admin/purchase/requisitions/{purchase_requisition}/submit', [PurchaseRequisitionController::class, 'submit'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.requisitions.submit');
        Route::post('/admin/purchase/requisitions/{purchase_requisition}/approve', [PurchaseRequisitionController::class, 'approve'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.requisitions.approve');
        Route::post('/admin/purchase/requisitions/{purchase_requisition}/convert', [PurchaseRequisitionController::class, 'convert'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.requisitions.convert');
        Route::post('/admin/purchase/requisitions/{purchase_requisition}/reject', [PurchaseRequisitionController::class, 'reject'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.requisitions.reject');
    });

    Route::middleware('permission:purchase.po.create|purchase.po.approve')->group(function (): void {
        Route::get('/admin/purchase/orders', [PurchaseOrderController::class, 'index'])->name('admin.purchase.orders.index');
        Route::post('/admin/purchase/orders/data', [PurchaseOrderController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.orders.data');
        Route::get('/admin/purchase/orders/{purchase_order}/pdf', [PurchaseOrderController::class, 'downloadPdf'])
            ->name('admin.purchase.orders.pdf');
    });
    Route::middleware('permission:purchase.po.create')->group(function (): void {
        Route::get('/admin/purchase/orders/create', [PurchaseOrderController::class, 'create'])->name('admin.purchase.orders.create');
        Route::post('/admin/purchase/orders', [PurchaseOrderController::class, 'store'])->name('admin.purchase.orders.store');
    });
    Route::middleware('permission:purchase.po.approve')->group(function (): void {
        Route::post('/admin/purchase/orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.orders.approve');
        Route::post('/admin/purchase/orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.orders.reject');
        Route::post('/admin/purchase/orders/{purchase_order}/mark-sent', [PurchaseOrderController::class, 'markSent'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.orders.mark-sent');
    });
    Route::middleware('permission:finance.payment.approve')->group(function (): void {
        Route::post('/admin/purchase/orders/{purchase_order}/finance-approve', [PurchaseOrderController::class, 'financeApprove'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.orders.finance-approve');
    });

    Route::middleware('permission:purchase.grn.create|inventory.grn')->group(function (): void {
        Route::get('/admin/purchase/grns', [GoodsReceiptController::class, 'index'])->name('admin.purchase.grns.index');
        Route::post('/admin/purchase/grns/data', [GoodsReceiptController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.grns.data');
        Route::get('/admin/purchase/grns/{goods_receipt}/pdf', [GoodsReceiptController::class, 'downloadPdf'])
            ->name('admin.purchase.grns.pdf');
        Route::get('/admin/purchase/grn-returns', [GrnReturnController::class, 'index'])->name('admin.purchase.grn-returns.index');
        Route::post('/admin/purchase/grn-returns/data', [GrnReturnController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.purchase.grn-returns.data');
    });
    Route::middleware('permission:purchase.grn.create')->group(function (): void {
        Route::get('/admin/purchase/grns/create', [GoodsReceiptController::class, 'create'])->name('admin.purchase.grns.create');
        Route::post('/admin/purchase/grns', [GoodsReceiptController::class, 'store'])->name('admin.purchase.grns.store');
        Route::post('/admin/purchase/grn-returns', [GrnReturnController::class, 'store'])->name('admin.purchase.grn-returns.store');
    });

    Route::middleware('permission:sales.quotation.create')->group(function (): void {
        Route::get('/admin/sales/quotations', [SalesQuotationController::class, 'index'])->name('admin.sales.quotations.index');
        Route::post('/admin/sales/quotations/data', [SalesQuotationController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.quotations.data');
        Route::get('/admin/sales/quotations/create', [SalesQuotationController::class, 'create'])->name('admin.sales.quotations.create');
        Route::post('/admin/sales/quotations', [SalesQuotationController::class, 'store'])->name('admin.sales.quotations.store');
        Route::post('/admin/sales/quotations/{sales_quotation}/send', [SalesQuotationController::class, 'send'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.quotations.send');
        Route::post('/admin/sales/quotations/{sales_quotation}/convert', [SalesQuotationController::class, 'convert'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.quotations.convert');
        Route::get('/admin/sales/quotations/{sales_quotation}/pdf', [SalesQuotationController::class, 'downloadPdf'])
            ->name('admin.sales.quotations.pdf');
        Route::get('/admin/sales/enquiries', [SalesEnquiryController::class, 'index'])->name('admin.sales.enquiries.index');
        Route::post('/admin/sales/enquiries/data', [SalesEnquiryController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.enquiries.data');
        Route::post('/admin/sales/enquiries', [SalesEnquiryController::class, 'store'])->name('admin.sales.enquiries.store');
    });

    Route::middleware('permission:sales.invoice.create')->group(function (): void {
        Route::get('/admin/sales/credit-notes', [CreditNoteController::class, 'index'])->name('admin.sales.credit-notes.index');
        Route::post('/admin/sales/credit-notes/data', [CreditNoteController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.credit-notes.data');
        Route::post('/admin/sales/credit-notes', [CreditNoteController::class, 'store'])->name('admin.sales.credit-notes.store');
        Route::post('/admin/sales/orders/{sales_order}/invoice', [SalesOrderController::class, 'invoice'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.orders.invoice');
        Route::get('/admin/sales/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
            ->name('admin.sales.invoices.pdf');
    });

    Route::middleware('permission:sales.order.create|sales.dispatch')->group(function (): void {
        Route::get('/admin/sales/orders', [SalesOrderController::class, 'index'])->name('admin.sales.orders.index');
        Route::post('/admin/sales/orders/data', [SalesOrderController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.orders.data');
        Route::get('/admin/sales/orders/{sales_order}/dispatch-data', [SalesOrderController::class, 'dispatchData'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.orders.dispatch-data');
        Route::get('/admin/sales/orders/{sales_order}/challan/pdf', [DispatchChallanController::class, 'downloadPdf'])
            ->name('admin.sales.orders.challan.pdf');
    });
    Route::middleware('permission:sales.order.create')->group(function (): void {
        Route::get('/admin/sales/orders/create', [SalesOrderController::class, 'create'])->name('admin.sales.orders.create');
        Route::post('/admin/sales/orders', [SalesOrderController::class, 'store'])->name('admin.sales.orders.store');
        Route::post('/admin/sales/orders/{sales_order}/process', [SalesOrderController::class, 'process'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.orders.process');
        Route::post('/admin/sales/orders/{sales_order}/suggest-production', [SalesOrderController::class, 'suggestProduction'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.orders.suggest-production');
    });
    Route::middleware('permission:sales.dispatch')->group(function (): void {
        Route::post('/admin/sales/orders/{sales_order}/dispatch', [SalesOrderController::class, 'dispatch'])
            ->middleware('throttle:120,1')
            ->name('admin.sales.orders.dispatch');
    });

    Route::middleware('permission:customers.edit')->group(function (): void {
        Route::get('/admin/sales/price-lists', [PriceListController::class, 'index'])->name('admin.sales.price-lists.index');
        Route::post('/admin/sales/price-lists', [PriceListController::class, 'store'])->name('admin.sales.price-lists.store');
        Route::post('/admin/sales/price-lists/{price_list}/items', [PriceListController::class, 'storeItem'])->name('admin.sales.price-lists.items.store');
        Route::get('/admin/sales/price-lists/{price_list}/items', [PriceListController::class, 'items'])->name('admin.sales.price-lists.items');
    });

    Route::middleware('role_or_permission:reports.sales|reports.purchase|reports.inventory|reports.finance|hr.employee.manage')->group(function (): void {
        Route::get('/admin/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
    });
    Route::middleware('permission:finance.reports.view')->group(function (): void {
        Route::get('/admin/reports/gstr1', [ReportsController::class, 'exportGstr1'])->name('admin.reports.gstr1');
        Route::get('/admin/reports/gstr1/json', [ReportsController::class, 'exportGstr1Json'])->name('admin.reports.gstr1.json');
        Route::get('/admin/reports/profit-loss', [ReportsController::class, 'exportProfitLoss'])->name('admin.reports.profit-loss');
        Route::get('/admin/reports/balance-sheet', [ReportsController::class, 'exportBalanceSheet'])->name('admin.reports.balance-sheet');
        Route::get('/admin/reports/chart-of-accounts', [ReportsController::class, 'chartOfAccounts'])->name('admin.reports.chart-of-accounts');
        Route::post('/admin/reports/gst-period/lock', [ReportsController::class, 'lockGstPeriod'])
            ->middleware('throttle:60,1')
            ->name('admin.reports.gst-period.lock');
        Route::get('/admin/reports/gstr3b', [ReportsController::class, 'exportGstr3b'])->name('admin.reports.gstr3b');
        Route::get('/admin/reports/gstr1/pdf', [ReportsController::class, 'exportGstr1Pdf'])->name('admin.reports.gstr1.pdf');
        Route::get('/admin/reports/gstr3b/pdf', [ReportsController::class, 'exportGstr3bPdf'])->name('admin.reports.gstr3b.pdf');
        Route::get('/admin/reports/vendors/{vendor}/statement/pdf', [ReportsController::class, 'exportVendorStatementPdf'])
            ->name('admin.reports.vendor-statement.pdf');
        Route::get('/admin/finance/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('admin.finance.chart-of-accounts.index');
        Route::get('/admin/finance/chart-of-accounts/data', [ChartOfAccountsController::class, 'data'])->name('admin.finance.chart-of-accounts.data');
        Route::post('/admin/finance/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('admin.finance.chart-of-accounts.store');
    });
    Route::middleware('permission:reports.inventory')->group(function (): void {
        Route::get('/admin/reports/stock-ledger/pdf', [ReportsController::class, 'exportStockLedgerPdf'])
            ->name('admin.reports.stock-ledger.pdf');
    });
    Route::middleware('permission:production.order.create|production.log')->group(function (): void {
        Route::get('/admin/production/work-orders/{productionOrder}/pdf', [ReportsController::class, 'exportProductionOrderPdf'])
            ->name('admin.production.work-orders.pdf');
    });

    Route::middleware('permission:finance.payment.approve')->group(function (): void {
        Route::get('/admin/finance/payments', [FinancePaymentController::class, 'index'])->name('admin.finance.payments.index');
        Route::post('/admin/finance/payments/data', [FinancePaymentController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.finance.payments.data');
        Route::post('/admin/finance/payments/vendor', [FinancePaymentController::class, 'storeVendorPayment'])
            ->name('admin.finance.payments.vendor');
        Route::post('/admin/finance/payments/customer', [FinancePaymentController::class, 'storeCustomerReceipt'])
            ->name('admin.finance.payments.customer');
        Route::post('/admin/finance/vendor-invoices/{vendorInvoice}/rematch', [VendorInvoiceController::class, 'rematch'])
            ->middleware('permission:finance.voucher.create')
            ->name('admin.finance.vendor-invoices.rematch');
    });

    Route::middleware('permission:hr.employee.manage')->group(function (): void {
        Route::get('/admin/hr/leave', [LeaveApplicationController::class, 'index'])->name('admin.hr.leave.index');
        Route::post('/admin/hr/leave/data', [LeaveApplicationController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.leave.data');
        Route::post('/admin/hr/leave', [LeaveApplicationController::class, 'store'])->name('admin.hr.leave.store');
        Route::post('/admin/hr/leave/{leave_application}/approve', [LeaveApplicationController::class, 'approve'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.leave.approve');
        Route::post('/admin/hr/leave/{leave_application}/reject', [LeaveApplicationController::class, 'reject'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.leave.reject');
    });

    Route::middleware('permission:production.bom.create')->group(function (): void {
        Route::get('/admin/production/boms', [BillOfMaterialController::class, 'index'])->name('admin.production.boms.index');
        Route::post('/admin/production/boms/data', [BillOfMaterialController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.production.boms.data');
        Route::get('/admin/production/boms/create', [BillOfMaterialController::class, 'create'])->name('admin.production.boms.create');
        Route::post('/admin/production/boms', [BillOfMaterialController::class, 'store'])->name('admin.production.boms.store');
    });

    Route::middleware('permission:production.order.create|production.log')->group(function (): void {
        Route::get('/admin/production/work-orders', [ProductionOrderController::class, 'index'])->name('admin.production.work-orders.index');
        Route::post('/admin/production/work-orders/data', [ProductionOrderController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.production.work-orders.data');
        Route::get('/admin/production/work-orders/{production_order}/edit', [ProductionOrderController::class, 'edit'])->name('admin.production.work-orders.edit');
        Route::put('/admin/production/work-orders/{production_order}', [ProductionOrderController::class, 'update'])->name('admin.production.work-orders.update');
    });
    Route::middleware('permission:production.order.create')->group(function (): void {
        Route::get('/admin/production/work-orders/create', [ProductionOrderController::class, 'create'])->name('admin.production.work-orders.create');
        Route::post('/admin/production/work-orders', [ProductionOrderController::class, 'store'])->name('admin.production.work-orders.store');
    });

    Route::middleware('permission:finance.voucher.create|finance.reports.view')->group(function (): void {
        Route::get('/admin/finance/vouchers', [JournalVoucherController::class, 'index'])->name('admin.finance.vouchers.index');
        Route::post('/admin/finance/vouchers/data', [JournalVoucherController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.finance.vouchers.data');
    });
    Route::middleware('permission:finance.voucher.create')->group(function (): void {
        Route::get('/admin/finance/vouchers/create', [JournalVoucherController::class, 'create'])->name('admin.finance.vouchers.create');
        Route::post('/admin/finance/vouchers', [JournalVoucherController::class, 'store'])->name('admin.finance.vouchers.store');
    });
    Route::middleware('permission:finance.payment.approve')->group(function (): void {
        Route::post('/admin/finance/vouchers/{journal_voucher}/post', [JournalVoucherController::class, 'post'])
            ->middleware('throttle:120,1')
            ->name('admin.finance.vouchers.post');
    });

    Route::middleware('permission:hr.employee.manage')->group(function (): void {
        Route::get('/admin/hr/employees', [EmployeeController::class, 'index'])->name('admin.hr.employees.index');
        Route::post('/admin/hr/employees/data', [EmployeeController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.employees.data');
        Route::get('/admin/hr/employees/create', [EmployeeController::class, 'create'])->name('admin.hr.employees.create');
        Route::post('/admin/hr/employees', [EmployeeController::class, 'store'])->name('admin.hr.employees.store');
        Route::get('/admin/hr/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.hr.employees.edit');
        Route::put('/admin/hr/employees/{employee}', [EmployeeController::class, 'update'])->name('admin.hr.employees.update');
        Route::delete('/admin/hr/employees/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.employees.destroy');
        Route::get('/admin/hr/departments', [DepartmentController::class, 'index'])->name('admin.hr.departments.index');
        Route::get('/admin/hr/departments/data', [DepartmentController::class, 'data'])->name('admin.hr.departments.data');
        Route::post('/admin/hr/departments', [DepartmentController::class, 'store'])->name('admin.hr.departments.store');
        Route::get('/admin/hr/designations', [DesignationController::class, 'index'])->name('admin.hr.designations.index');
        Route::get('/admin/hr/designations/data', [DesignationController::class, 'data'])->name('admin.hr.designations.data');
        Route::post('/admin/hr/designations', [DesignationController::class, 'store'])->name('admin.hr.designations.store');
        Route::get('/admin/hr/payroll-settings', [PayrollSettingsController::class, 'edit'])
            ->name('admin.hr.payroll-settings.edit');
        Route::put('/admin/hr/payroll-settings', [PayrollSettingsController::class, 'update'])
            ->name('admin.hr.payroll-settings.update');
        Route::get('/admin/hr/allowance-types', [AllowanceTypeController::class, 'index'])
            ->name('admin.hr.allowance-types.index');
        Route::get('/admin/hr/allowance-types/data', [AllowanceTypeController::class, 'data'])
            ->name('admin.hr.allowance-types.data');
        Route::post('/admin/hr/allowance-types', [AllowanceTypeController::class, 'store'])
            ->name('admin.hr.allowance-types.store');
        Route::put('/admin/hr/allowance-types/{allowanceType}', [AllowanceTypeController::class, 'update'])
            ->name('admin.hr.allowance-types.update');
    });

    Route::middleware('permission:hr.attendance.mark')->group(function (): void {
        Route::get('/admin/hr/attendance', [AttendanceController::class, 'index'])->name('admin.hr.attendance.index');
        Route::post('/admin/hr/attendance/data', [AttendanceController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.attendance.data');
        Route::post('/admin/hr/attendance', [AttendanceController::class, 'store'])->name('admin.hr.attendance.store');
    });

    Route::middleware('permission:hr.payroll.run')->group(function (): void {
        Route::get('/admin/hr/payroll-runs', [PayrollRunController::class, 'index'])->name('admin.hr.payroll-runs.index');
        Route::post('/admin/hr/payroll-runs/data', [PayrollRunController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.payroll-runs.data');
        Route::get('/admin/hr/payroll-runs/create', [PayrollRunController::class, 'create'])->name('admin.hr.payroll-runs.create');
        Route::post('/admin/hr/payroll-runs', [PayrollRunController::class, 'store'])->name('admin.hr.payroll-runs.store');
        Route::post('/admin/hr/payroll-runs/{payroll_run}/process', [PayrollRunController::class, 'process'])
            ->middleware('throttle:120,1')
            ->name('admin.hr.payroll-runs.process');
        Route::get('/admin/hr/payroll-runs/{payroll_run}', [PayrollRunController::class, 'show'])
            ->name('admin.hr.payroll-runs.show');
        Route::get('/admin/hr/payroll-runs/{payroll_run}/pdf', [PayrollRunController::class, 'downloadPdf'])
            ->name('admin.hr.payroll-runs.pdf');
        Route::get('/admin/hr/payroll-details/{payroll_detail}/pdf', [PayrollDetailController::class, 'downloadPdf'])
            ->name('admin.hr.payroll-details.pdf');
    });

    Route::middleware(['auth', 'permission:hr.payslip.view|hr.attendance.view|hr.leave.apply'])->prefix('employee')->name('employee.')->group(function (): void {
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/data', [EmployeeAttendanceController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('attendance.data');
        Route::get('/leave', [EmployeeLeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave/data', [EmployeeLeaveController::class, 'data'])
            ->middleware('throttle:120,1')
            ->name('leave.data');
        Route::post('/leave', [EmployeeLeaveController::class, 'store'])->name('leave.store');
        Route::get('/payslips', [EmployeePayslipController::class, 'index'])->name('payslips.index');
        Route::get('/payslips/{payroll_detail}/pdf', [EmployeePayslipController::class, 'downloadPdf'])
            ->name('payslips.pdf');
        Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
    });
});

Route::prefix('vendor')->name('vendor.portal.')->group(function (): void {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    Route::middleware('auth:vendor')->group(function (): void {
        Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [PortalAuthController::class, 'dashboard'])->name('dashboard');
        Route::post('/purchase-orders/{purchase_order}/accept', [PortalAuthController::class, 'acceptPo'])
            ->name('po.accept');
        Route::post('/vendor-invoices', [PortalAuthController::class, 'storeVendorInvoice'])
            ->name('vendor-invoices.store');
        Route::post('/purchase-orders/{purchase_order}/reject', [PortalAuthController::class, 'rejectPo'])
            ->name('po.reject');
        Route::post('/purchase-orders/{purchase_order}/delivery', [PortalAuthController::class, 'updateDelivery'])
            ->name('po.delivery');
    });
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController; 


Route::get('/', function () {
    return view('auth.login');
});


Auth::routes();
//Auth::routes(['register' => false]);

Route::get('/home', 'HomeController@index')->name('home');

Route::resource('clients', ClientController::class)->middleware(['auth']);

Route::resource('projects', ProjectController::class)->middleware(['auth']);

// مسارات فريق المشروع (متداخلة ضمن المشاريع) - بالطريقة الفردية
Route::resource('projects.team', ProjectTeamController::class)->except(['show'])->middleware(['auth'])->names([
    'index' => 'projects.team.index',
    'create' => 'projects.team.create',
    'store' => 'projects.team.store',
    'edit' => 'projects.team.edit',
    'update' => 'projects.team.update',
    'destroy' => 'projects.team.destroy',
]);

// **مسارات المهام (متداخلة ضمن المشاريع) - بالطريقة الفردية**
Route::resource('projects.tasks', TaskController::class)->middleware(['auth'])->names([
    'index' => 'projects.tasks.index',
    'create' => 'projects.tasks.create',
    'store' => 'projects.tasks.store',
    'show' => 'projects.tasks.show',
    'edit' => 'projects.tasks.edit',
    'update' => 'projects.tasks.update',
    'destroy' => 'projects.tasks.destroy',
]);

Route::resource('contracts', ContractController::class);

Route::resource('suppliers', SupplierController::class);

Route::resource('expenses', ExpenseController::class);

Route::resource('incomes', IncomeController::class);


// مسارات الفواتير
Route::resource('invoices', InvoiceController::class);

// مسارات الفواتير الإضافية
Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
Route::get('invoices/generate-number', [App\Http\Controllers\InvoiceController::class, 'generateUniqueInvoiceNumber'])->name('invoices.generate_number');
Route::post('invoices/{invoice}/update-payment-status', [InvoiceController::class, 'updatePaymentStatus'])->name('invoices.update_payment_status');
Route::get('/test-invoice-controller-load', [InvoiceController::class, 'index']);

Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');



Route::get('attachments', [App\Http\Controllers\AttachmentController::class, 'index'])->name('attachments.index');
Route::post('attachments', [App\Http\Controllers\AttachmentController::class, 'store'])->name('attachments.store');
Route::get('attachments/{attachment}/download', [App\Http\Controllers\AttachmentController::class, 'download'])->name('attachments.download');
Route::delete('attachments/{attachment}', [App\Http\Controllers\AttachmentController::class, 'destroy'])->name('attachments.destroy');

Route::resource('payments', PaymentController::class);

Route::resource('taxes', TaxController::class);
Route::get('taxes/vat-report', [App\Http\Controllers\TaxController::class, 'vatReport'])->name('taxes.vat_report'); // <== أضف هذا السطر

Route::resource('units', UnitController::class);

Route::resource('material_groups', MaterialGroupController::class);

Route::resource('materials', MaterialController::class);

Route::get('settings', [App\Http\Controllers\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');


Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::delete('notifications/{notification}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('notifications/{notification}/mark-as-read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('notifications/{notification}/mark-as-unread', [App\Http\Controllers\NotificationController::class, 'markAsUnread'])->name('notifications.markAsUnread');
    Route::get('notifications/mark-all-as-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');


Route::resource('timesheets', TimesheetController::class);

Route::resource('warehouses', WarehouseController::class);

Route::resource('inventory', InventoryController::class);

Route::resource('stock_movements', StockMovementController::class);

Route::get('audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])->name('audit_logs.index');
    Route::get('audit-logs/{auditLog}', [App\Http\Controllers\AuditLogController::class, 'show'])->name('audit_logs.show');


Route::resource('purchase_invoices', PurchaseInvoiceController::class);

Route::resource('purchase_payments', PurchasePaymentController::class);




Route::get('/{page}', 'AdminController@index');
<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TaskController;
use App\Support\Documentable;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(Roles::dashboardRouteFor(auth()->user()));
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:'.Roles::SUPER_ADMIN])
    ->prefix('super')
    ->group(function () {
        Route::get('/', fn () => view('roles.super'))->name('roles.super');
    });

Route::middleware(['auth', 'verified', 'role:'.Roles::ADMIN.'|'.Roles::SUPER_ADMIN])
    ->prefix('admin')
    ->group(function () {
        Route::get('/', fn () => view('roles.admin'))->name('roles.admin');
    });

Route::middleware(['auth', 'verified', 'role:'.Roles::MANAGER.'|'.Roles::ADMIN.'|'.Roles::SUPER_ADMIN])
    ->prefix('manager')
    ->group(function () {
        Route::get('/', fn () => view('roles.manager'))->name('roles.manager');
    });

Route::middleware(['auth', 'verified', 'role:'.Roles::ACCOUNTANT.'|'.Roles::ADMIN.'|'.Roles::SUPER_ADMIN])
    ->prefix('accountant')
    ->group(function () {
        Route::get('/', fn () => view('roles.accountant'))->name('roles.accountant');
    });

Route::middleware(['auth', 'verified', 'role:'.Roles::EMPLOYEE.'|'.Roles::MANAGER.'|'.Roles::ADMIN.'|'.Roles::SUPER_ADMIN])
    ->prefix('employee')
    ->group(function () {
        Route::get('/', fn () => view('roles.employee'))->name('roles.employee');
    });

Route::middleware(['auth', 'verified', 'role:'.Roles::CLIENT])
    ->prefix('client')
    ->group(function () {
        Route::get('/', fn () => view('roles.client'))->name('roles.client');
    });

Route::resource('customers', CustomerController::class)
    ->middleware(['auth', 'verified']);

Route::resource('projects', ProjectController::class)
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->prefix('projects/{project}/members')->name('projects.members.')->group(function () {
    Route::post('/', [ProjectController::class, 'addMember'])->name('store');
    Route::patch('/{member}', [ProjectController::class, 'updateMemberRole'])->name('update');
    Route::delete('/{member}', [ProjectController::class, 'removeMember'])->name('destroy');
});

Route::resource('tasks', TaskController::class)
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->prefix('tasks/{task}')->name('tasks.')->group(function () {
    Route::patch('/status', [TaskController::class, 'updateStatus'])->name('status.update');
    Route::post('/assignees', [TaskController::class, 'addAssignee'])->name('assignees.store');
    Route::delete('/assignees/{user}', [TaskController::class, 'removeAssignee'])->name('assignees.destroy');
});

Route::resource('services', ServiceController::class)
    ->except('show')
    ->middleware(['auth', 'verified']);

Route::resource('products', ProductController::class)
    ->except('show')
    ->middleware(['auth', 'verified']);

Route::resource('invoices', InvoiceController::class)
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->prefix('invoices/{invoice}')->name('invoices.')->group(function () {
    Route::post('/items', [InvoiceController::class, 'addItem'])->name('items.store');
    Route::patch('/items/{item}', [InvoiceController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{item}', [InvoiceController::class, 'removeItem'])->name('items.destroy');
    Route::post('/mark-sent', [InvoiceController::class, 'markSent'])->name('mark-sent');
    Route::post('/mark-void', [InvoiceController::class, 'markVoid'])->name('mark-void');
    Route::get('/print', [InvoiceController::class, 'print'])->name('print');
});

Route::resource('payments', PaymentController::class)
    ->except('edit')
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->prefix('expenses')->name('expenses.')->group(function () {
    Route::get('/report', [ExpenseController::class, 'report'])->name('report');
    Route::get('/export', [ExpenseController::class, 'export'])->name('export');
});

Route::resource('expenses', ExpenseController::class)
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])
    ->prefix('{type}/{id}/documents')
    ->whereIn('type', array_keys(Documentable::map()))
    ->whereNumber('id')
    ->name('documents.')
    ->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{media}/download', [DocumentController::class, 'download'])->name('download');
        Route::delete('/{media}', [DocumentController::class, 'destroy'])->name('destroy');
    });

// Admin-only modules: gated by the 'viewAdmin' Gate (ADMIN or SUPER_ADMIN),
// matching the @can('viewAdmin') check that hides these links in the nav.
Route::middleware(['auth', 'verified', 'can:viewAdmin'])->group(function () {
    $adminModules = [
        'users' => 'Users',
        'settings' => 'Settings',
    ];

    foreach ($adminModules as $slug => $title) {
        Route::get("/{$slug}", fn () => view('modules.coming-soon', ['title' => $title]))
            ->name("{$slug}.index");
    }
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

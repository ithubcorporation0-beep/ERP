<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    $modules = [
        'tasks' => 'Tasks',
        'invoices' => 'Invoices',
        'payments' => 'Payments',
        'expenses' => 'Expenses',
        'documents' => 'Documents',
    ];

    foreach ($modules as $slug => $title) {
        Route::get("/{$slug}", fn () => view('modules.coming-soon', ['title' => $title]))
            ->name("{$slug}.index");
    }
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

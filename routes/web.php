<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    $modules = [
        'customers' => 'Customers',
        'projects' => 'Projects',
        'tasks' => 'Tasks',
        'invoices' => 'Invoices',
        'payments' => 'Payments',
        'expenses' => 'Expenses',
        'documents' => 'Documents',
        'users' => 'Users',
        'settings' => 'Settings',
    ];

    foreach ($modules as $slug => $title) {
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

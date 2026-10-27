<?php

use App\Http\Controllers\AgentPluginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('agent-plugin/download', [AgentPluginController::class, 'download'])
        ->name('agent-plugin.download');

    Route::resource('clients', ClientController::class)
        ->except(['show', 'create'])
        ->parameters(['clients' => 'client']);

    Route::resource('sites', SiteController::class)->except(['create']);
    Route::post('sites/{site}/scan', [SiteController::class, 'scan'])->name('sites.scan');
});

require __DIR__.'/settings.php';

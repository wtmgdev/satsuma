<?php

use App\Http\Controllers\AgentPluginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');
Route::inertia('/privacy', 'legal/privacy')->name('privacy');
Route::inertia('/cookies', 'legal/cookies')->name('cookies');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('agent-plugin/download', [AgentPluginController::class, 'download'])
        ->name('agent-plugin.download');

    Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::post('organizations/switch', [OrganizationController::class, 'switch'])->name('organizations.switch');

    Route::get('settings/organization', [OrganizationController::class, 'edit'])->name('organizations.settings');
    Route::patch('settings/organization', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::post('settings/organization/members', [OrganizationController::class, 'addMember'])->name('organizations.members.add');
    Route::patch('settings/organization/members/{member}', [OrganizationController::class, 'updateMember'])->name('organizations.members.update');
    Route::delete('settings/organization/members/{member}', [OrganizationController::class, 'removeMember'])->name('organizations.members.remove');

    Route::resource('clients', ClientController::class)
        ->except(['show', 'create'])
        ->parameters(['clients' => 'client']);

    Route::resource('sites', SiteController::class)->except(['create', 'edit']);
    Route::post('sites/{site}/scan', [SiteController::class, 'scan'])->name('sites.scan');
    Route::post('sites/{site}/ping', [SiteController::class, 'ping'])->name('sites.ping');
    Route::post('sites/{site}/pagespeed', [SiteController::class, 'runPageSpeed'])->name('sites.pagespeed');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('usage', [UsageController::class, 'index'])->name('usage');
    Route::post('notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
});

require __DIR__.'/settings.php';

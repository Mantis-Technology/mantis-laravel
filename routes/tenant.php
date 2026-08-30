<?php

declare(strict_types=1);

use App\Http\Controllers\parameterization\MaintenanceCategoryController;
use App\Http\Controllers\permissions\PermissionController;
use App\Http\Controllers\roles\RoleController;
use App\Http\Controllers\TenantLogoController;
use App\Http\Controllers\users\UserController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::inertia('/', 'welcome')->name('home');

    Route::middleware('auth')->group(function () {
        Route::get('/tenant/logo', TenantLogoController::class)
            ->name('tenant.logo');

        Route::inertia('/dashboard', 'dashboard')->name('dashboard');

        Route::group([
            'prefix' => 'parameterization',
            'as' => 'parameterization.',
        ], function () {
            Route::group([
                'prefix' => 'maintenance-categories',
                'as' => 'maintenance-categories.',
            ], function () {
                Route::get('/', [MaintenanceCategoryController::class, 'index'])
                    ->name('index');

                Route::get('/create', [MaintenanceCategoryController::class, 'create'])
                    ->name('create');

                Route::post('/', [MaintenanceCategoryController::class, 'store'])
                    ->name('store');

                Route::get('/{id}/edit', [MaintenanceCategoryController::class, 'edit'])
                    ->name('edit');

                Route::put('/{id}', [MaintenanceCategoryController::class, 'update'])
                    ->name('update');

                Route::delete('/{id}', [MaintenanceCategoryController::class, 'destroy'])
                    ->name('destroy');

                Route::patch('/{id}/active', [MaintenanceCategoryController::class, 'toggleActive'])
                    ->name('toggle-active')
                    ->middleware('role:maintenance_chief|tenant_admin');
            });
        });

        Route::inertia('/profile', 'Profile/edit')->name('profile.edit');
    });
});

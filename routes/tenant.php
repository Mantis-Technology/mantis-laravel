<?php

declare(strict_types=1);

use App\Http\Controllers\AccessPortalController;
use App\Http\Controllers\CompanyPortalController;
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

    Route::get('/access', AccessPortalController::class)->name('access');

    Route::get('/portal', CompanyPortalController::class)->name('portal');

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

        Route::middleware('role:tenant_admin')->group(function () {
            Route::prefix('users')
                ->as('users.')
                ->group(function () {
                    Route::get('/', [UserController::class, 'index'])
                        ->name('index');

                    Route::get('/create', [UserController::class, 'create'])
                        ->name('create');

                    Route::post('/', [UserController::class, 'store'])
                        ->name('store');

                    Route::get('/{id}/edit', [UserController::class, 'edit'])
                        ->name('edit');

                    Route::put('/{id}', [UserController::class, 'update'])
                        ->name('update');

                    Route::delete('/{id}', [UserController::class, 'destroy'])
                        ->name('destroy');

                    Route::post('/{id}/restore', [UserController::class, 'restore'])
                        ->name('restore');
                });

            Route::prefix('roles')
                ->as('roles.')
                ->group(function () {
                    Route::get('/', [RoleController::class, 'index'])
                        ->name('index');

                    Route::get('/create', [RoleController::class, 'create'])
                        ->name('create');

                    Route::post('/', [RoleController::class, 'store'])
                        ->name('store');

                    Route::get('/{id}/edit', [RoleController::class, 'edit'])
                        ->name('edit');

                    Route::put('/{id}', [RoleController::class, 'update'])
                        ->name('update');

                    Route::delete('/{id}', [RoleController::class, 'destroy'])
                        ->name('destroy');
                });

            Route::prefix('permissions')
                ->as('permissions.')
                ->group(function () {
                    Route::get('/', [PermissionController::class, 'index'])
                        ->name('index');

                    Route::get('/create', [PermissionController::class, 'create'])
                        ->name('create');

                    Route::post('/', [PermissionController::class, 'store'])
                        ->name('store');

                    Route::get('/{id}/edit', [PermissionController::class, 'edit'])
                        ->name('edit');

                    Route::put('/{id}', [PermissionController::class, 'update'])
                        ->name('update');

                    Route::delete('/{id}', [PermissionController::class, 'destroy'])
                        ->name('destroy');
                });
        });

        Route::inertia('/profile', 'Profile/edit')->name('profile.edit');
    });
});

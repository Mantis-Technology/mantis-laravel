<?php

use App\Http\Controllers\AccessPortalController;
use App\Http\Controllers\CompanyPortalController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\VerifyCompanyDomainController;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        Route::inertia('/', 'welcome')->name('home');
        Route::get('/access', AccessPortalController::class)->name('access');
        Route::get('/portal', CompanyPortalController::class)->name('portal');
        Route::get('/portal/verify/{slug}', VerifyCompanyDomainController::class)
            ->name('portal.verify');

        Route::prefix('register/company')->name('registration.')->group(function () {
            Route::get('/', [CompanyRegistrationController::class, 'create'])
                ->name('create');
            Route::post('/', [CompanyRegistrationController::class, 'store'])
                ->name('store');
            Route::get('/success', [CompanyRegistrationController::class, 'success'])
                ->name('success');
        });
    });
}

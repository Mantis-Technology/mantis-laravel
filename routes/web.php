<?php

use App\Http\Controllers\AccessPortalController;
use App\Http\Controllers\CompanyPortalController;
use App\Http\Controllers\CompanyRegistrationController;
use App\Http\Controllers\VerifyCompanyDomainController;
use Illuminate\Support\Facades\Route;

foreach (array_values(config('tenancy.central_domains')) as $index => $domain) {
    $suffix = $index === 0 ? '' : '_'.$index;

    Route::domain($domain)->group(function () use ($suffix) {
        Route::inertia('/', 'Welcome')->name('home'.$suffix);
        Route::get('/access', AccessPortalController::class)->name('access'.$suffix);
        Route::get('/portal', CompanyPortalController::class)->name('portal'.$suffix);
        Route::get('/portal/verify/{slug}', VerifyCompanyDomainController::class)
            ->name('portal'.$suffix.'.verify');

        Route::prefix('register/company')->name('registration'.$suffix.'.')->group(function () {
            Route::get('/', [CompanyRegistrationController::class, 'create'])
                ->name('create');
            Route::post('/', [CompanyRegistrationController::class, 'store'])
                ->name('store');
            Route::get('/success', [CompanyRegistrationController::class, 'success'])
                ->name('success');
        });
    });
}

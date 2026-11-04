<?php

use App\Http\Controllers\Business\AccountController;
use App\Http\Controllers\Business\AuthController;
use App\Http\Controllers\Business\BookingController;
use App\Http\Controllers\Business\CompanyRequestController;
use App\Http\Controllers\Business\DashboardController;
use App\Http\Controllers\Business\InvitationController;
use App\Http\Controllers\Business\PasswordResetController;
use App\Http\Controllers\Business\TeamController;
use App\Http\Controllers\Business\WalletController;
use App\Http\Middleware\EnsureCompanyAdmin;
use App\Http\Middleware\EnsureCompanyUser;
use Illuminate\Support\Facades\Route;

/*
 * The corporate portal. Loaded by routes/web.php, so these have the web
 * middleware. See docs/corporate-flights-plan.md.
 */
Route::prefix('business')->name('business.')->group(function () {
    Route::get('/request', [CompanyRequestController::class, 'create'])->name('request.create');
    Route::post('/request', [CompanyRequestController::class, 'store'])->middleware('throttle:5,10')->name('request.store');
    Route::get('/request/received', [CompanyRequestController::class, 'received'])->name('request.received');

    Route::get('/invitations/{token}', [InvitationController::class, 'show'])->middleware('throttle:30,1')->name('invitation.show');
    Route::post('/invitations/{token}', [InvitationController::class, 'accept'])->middleware('throttle:10,1')->name('invitation.accept');

    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
    Route::get('/login/code', [AuthController::class, 'showCode'])->name('login.code');
    Route::post('/login/code', [AuthController::class, 'verifyCode'])->middleware('throttle:10,1')->name('login.code.verify');
    Route::post('/login/code/resend', [AuthController::class, 'resendCode'])->middleware('throttle:5,1')->name('login.code.resend');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');

    // SeerBit sends the browser here, and the session may have ended by then.
    Route::get('/wallet/callback', [WalletController::class, 'callback'])->middleware('throttle:30,1')->name('wallet.callback');

    Route::middleware(EnsureCompanyUser::class)->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::middleware(EnsureCompanyAdmin::class)->group(function () {
            Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
            Route::post('/wallet/top-ups', [WalletController::class, 'store'])->middleware('throttle:10,1')->name('wallet.top-ups.store');
        });

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{reference}/ticket', [BookingController::class, 'ticket'])->name('bookings.ticket');

        Route::get('/account', [AccountController::class, 'show'])->name('account');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
        Route::put('/account/login-code', [AccountController::class, 'updateLoginCode'])->name('account.login-code');

        Route::middleware(EnsureCompanyAdmin::class)->prefix('team')->name('team.')->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::post('/invitations', [TeamController::class, 'invite'])->name('invite');
            Route::post('/invitations/{invitation}/resend', [TeamController::class, 'resend'])->whereNumber('invitation')->name('invitations.resend');
            Route::delete('/invitations/{invitation}', [TeamController::class, 'revoke'])->whereNumber('invitation')->name('invitations.revoke');
            Route::put('/members/{member}/role', [TeamController::class, 'updateRole'])->whereNumber('member')->name('members.role');
            Route::put('/members/{member}/deactivate', [TeamController::class, 'deactivate'])->whereNumber('member')->name('members.deactivate');
            Route::put('/members/{member}/reactivate', [TeamController::class, 'reactivate'])->whereNumber('member')->name('members.reactivate');
        });
    });
});

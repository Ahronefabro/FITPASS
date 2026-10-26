<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPasswordResetController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Staff\CheckInController as StaffCheckInController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\ManualTimeController;
use App\Http\Controllers\Staff\MemberController as StaffMemberController;
use App\Http\Controllers\Staff\NotificationController;
use App\Http\Controllers\Staff\PaymentController;
use App\Http\Controllers\Staff\ScannerController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// Signing in. One form for everyone; the role decides which portal opens.
// ---------------------------------------------------------------------

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

// Members of the mobile app reset their password here, from the emailed link.
// Not behind the guest check: members never sign in to the website.
Route::get('/member/reset-password/{token}', [MemberPasswordResetController::class, 'edit'])->name('member.password.reset');
Route::post('/member/reset-password', [MemberPasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('member.password.update');

// The staff portal used to have a sign-in page of its own. Keep the old url
// working for anyone who bookmarked it.
Route::redirect('/staff/login', '/login');

// Outside the portal groups, so anyone signed in can sign out, and manage
// their own account, whatever their role has since become.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::patch('/account/username', [AccountController::class, 'updateUsername'])->name('account.username');
    Route::patch('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
});

// ---------------------------------------------------------------------
// Owner portal
// ---------------------------------------------------------------------

Route::middleware(['auth', 'portal:owner'])->group(function () {
    Route::get('/', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/analytics', [DashboardController::class, 'analytics'])->name('analytics');
    Route::get('/audit-logs', [DashboardController::class, 'auditLogs'])->name('audit-logs');

    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberController::class, 'store'])->name('members.store');

    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::patch('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');

    Route::get('/check-ins', [CheckInController::class, 'index'])->name('checkins.index');
    Route::post('/check-ins', [CheckInController::class, 'store'])->name('checkins.store');
    Route::get('/check-ins/export', [CheckInController::class, 'export'])->name('checkins.export');
    Route::patch('/check-ins/{checkIn}/checkout', [CheckInController::class, 'checkout'])->name('checkins.checkout');
});

// ---------------------------------------------------------------------
// Staff portal (the staff_web screens)
// ---------------------------------------------------------------------

Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware(['auth', 'portal:staff'])->group(function () {
        Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

        Route::get('/check-ins', [StaffCheckInController::class, 'index'])->name('checkins');
        Route::post('/check-ins', [StaffCheckInController::class, 'store'])->name('checkins.store');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

        Route::get('/members', [StaffMemberController::class, 'index'])->name('members');
        Route::post('/members', [StaffMemberController::class, 'store'])->name('members.store');
        Route::patch('/members/{member}', [StaffMemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [StaffMemberController::class, 'destroy'])->name('members.destroy');
        Route::post('/members/{pending}/approve', [StaffMemberController::class, 'approve'])->name('members.approve');
        Route::delete('/members/{pending}/reject', [StaffMemberController::class, 'reject'])->name('members.reject');

        Route::get('/manual-time', [ManualTimeController::class, 'index'])->name('manual-time');
        Route::post('/manual-time', [ManualTimeController::class, 'store'])->name('manual-time.store');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');

        Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner');
        Route::post('/scanner/record', [ScannerController::class, 'store'])->name('scanner.record');
    });
});
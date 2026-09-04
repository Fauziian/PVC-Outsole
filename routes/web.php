<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirection root page ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Middleware auth untuk semua modul
Route::middleware(['auth'])->group(function () {
    
    // 1. Dashboard (Aksesible untuk semua role terautentikasi)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. Modul Admin (Role: admin)
    Route::middleware(['role:admin'])->group(function () {
        // Manajemen Pengguna
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::put('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        
        // Pengaturan Gaji
        Route::get('/payroll/settings', [PayrollController::class, 'settings'])->name('payroll.settings');
        Route::post('/payroll/settings', [PayrollController::class, 'updateSettings'])->name('payroll.settings.update');
    });

    // 3. Modul HR / Keuangan dan Penggajian (Role: hr)
    Route::middleware(['role:hr'])->group(function () {
        // Data Karyawan
        Route::get('/employees', [KaryawanController::class, 'index'])->name('employees.index');
        Route::post('/employees', [KaryawanController::class, 'store'])->name('employees.store');
        Route::put('/employees/{employee}', [KaryawanController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [KaryawanController::class, 'destroy'])->name('employees.destroy');

        // Kehadiran / Absensi
        Route::get('/attendance', [AbsensiController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/check-in', [AbsensiController::class, 'checkIn'])->name('attendance.check-in');
        Route::put('/attendance/{attendance}/check-out', [AbsensiController::class, 'checkOut'])->name('attendance.check-out');
        Route::delete('/attendance/{attendance}', [AbsensiController::class, 'destroy'])->name('attendance.destroy');

        // Komponen Gaji & Slip
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/calculate', [PayrollController::class, 'calculatePeriod'])->name('payroll.calculate');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::put('/payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::get('/payroll/{payroll}/pdf', [PayrollController::class, 'downloadPdf'])->name('payroll.pdf');

        // Laporan HR (Unduh PDF/Excel)
        Route::get('/reports/attendance/pdf', [ReportController::class, 'downloadAttendancePdf'])->name('reports.attendance.pdf');
        Route::get('/reports/payroll/excel', [ReportController::class, 'exportPayrollExcel'])->name('reports.payroll.excel');
    });

    // 4. Modul Gudang / Stock (Role: warehouse)
    Route::middleware(['role:warehouse'])->group(function () {
        // Transaksi Barang Masuk
        Route::get('/stock/incoming', [StockController::class, 'incoming'])->name('stock.incoming');
        Route::post('/stock/incoming', [StockController::class, 'storeIncoming'])->name('stock.incoming.store');

        // Transaksi Barang Keluar
        Route::get('/stock/outgoing', [StockController::class, 'outgoing'])->name('stock.outgoing');
        Route::post('/stock/outgoing', [StockController::class, 'storeOutgoing'])->name('stock.outgoing.store');

        // Laporan mingguan mutasi barang jadi
        Route::get('/stock/weekly-report', [StockController::class, 'weeklyReport'])->name('stock.weekly-report');
        Route::get('/stock/report/excel', [StockController::class, 'exportStockReportExcel'])->name('stock.report.excel');
        Route::get('/stock/report/pdf', [StockController::class, 'exportStockReportPdf'])->name('stock.report.pdf');

    });

    // 5. Laporan HR/Keuangan
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware(['role:hr'])
        ->name('reports.index');

    // Profile standard breeze routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

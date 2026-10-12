<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BebasHarianController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstansiController;
use App\Http\Controllers\IntegrasiController;
use App\Http\Controllers\KroscekController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\OverstayingController;
use App\Http\Controllers\PenghuniController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Public Routes (Dashboard is public like in native PHP)
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Public API endpoint
Route::get('/api/stats', [ApiController::class, 'stats'])->name('api.stats');

// Public Cron WhatsApp endpoint (matches native cron-wa.php)
Route::get('/cron-wa', function (\Illuminate\Http\Request $request) {
    $secretKey = 'ditjenpas_banten_2026_secret';
    if ($request->query('key') !== $secretKey) {
        return response()->json(['success' => false, 'message' => 'Invalid secret key'], 403);
    }
    $action = $request->query('action', 'run');
    $result = \App\Services\WhatsAppService::runCron($action);
    return response()->json($result);
})->name('cron.wa');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Must be logged in)
Route::middleware('auth')->group(function () {
    // Kroscek Data
    Route::get('/kroscek', [KroscekController::class, 'index'])->name('kroscek.index');

    // Data Penghuni
    Route::get('/penghuni', [PenghuniController::class, 'index'])->name('penghuni.index');
    Route::get('/penghuni/previous-data', [PenghuniController::class, 'getPreviousData'])->name('penghuni.previous-data');
    Route::post('/penghuni', [PenghuniController::class, 'store'])->name('penghuni.store');
    Route::put('/penghuni/{id}', [PenghuniController::class, 'update'])->name('penghuni.update');
    Route::delete('/penghuni/{id}', [PenghuniController::class, 'destroy'])->name('penghuni.destroy');

    // Data Overstaying
    Route::get('/overstaying', [OverstayingController::class, 'index'])->name('overstaying.index');
    Route::post('/overstaying', [OverstayingController::class, 'store'])->name('overstaying.store');
    Route::put('/overstaying/{id}', [OverstayingController::class, 'update'])->name('overstaying.update');
    Route::delete('/overstaying/{id}', [OverstayingController::class, 'destroy'])->name('overstaying.destroy');

    // Data Integrasi
    Route::get('/integrasi', [IntegrasiController::class, 'index'])->name('integrasi.index');
    Route::post('/integrasi', [IntegrasiController::class, 'store'])->name('integrasi.store');
    Route::put('/integrasi/{id}', [IntegrasiController::class, 'update'])->name('integrasi.update');
    Route::delete('/integrasi/{id}', [IntegrasiController::class, 'destroy'])->name('integrasi.destroy');

    // Bebas Harian Routes
    Route::get('/bebas-harian', [BebasHarianController::class, 'index'])->name('bebas-harian.index');
    Route::get('/bebas-harian/detail', [BebasHarianController::class, 'detail'])->name('bebas-harian.detail');
    Route::post('/bebas-harian', [BebasHarianController::class, 'store'])->name('bebas-harian.store');
    Route::put('/bebas-harian', [BebasHarianController::class, 'update'])->name('bebas-harian.update');
    Route::delete('/bebas-harian', [BebasHarianController::class, 'destroy'])->name('bebas-harian.destroy');

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
    Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');

    // Data UPT
    Route::get('/upt', [UptController::class, 'index'])->name('upt.index');
    Route::post('/upt', [UptController::class, 'store'])->name('upt.store');
    Route::put('/upt/{id}', [UptController::class, 'update'])->name('upt.update');
    Route::delete('/upt/{id}', [UptController::class, 'destroy'])->name('upt.destroy');

    // Manajemen User
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    // Pengaturan Instansi
    Route::get('/instansi', [InstansiController::class, 'index'])->name('instansi.index');
    Route::match(['put', 'post'], '/instansi', [InstansiController::class, 'update'])->name('instansi.update');

    // Pengumuman
    Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::post('/pengumuman', [PengumumanController::class, 'store'])->name('pengumuman.store');
    Route::put('/pengumuman/{id}', [PengumumanController::class, 'update'])->name('pengumuman.update');
    Route::delete('/pengumuman/{id}', [PengumumanController::class, 'destroy'])->name('pengumuman.destroy');

    // WhatsApp Gateway Settings
    Route::get('/wa-settings', [WhatsAppController::class, 'settingsIndex'])->name('wa.settings');
    Route::post('/wa-settings', [WhatsAppController::class, 'settingsUpdate'])->name('wa.settings.update');
    Route::post('/wa-settings/test', [WhatsAppController::class, 'settingsTest'])->name('wa.settings.test');

    // WhatsApp Scheduled Reports
    Route::get('/wa-schedule', [WhatsAppController::class, 'scheduleIndex'])->name('wa.schedule');
    Route::post('/wa-schedule', [WhatsAppController::class, 'scheduleStore'])->name('wa.schedule.store');
    Route::put('/wa-schedule/{id}', [WhatsAppController::class, 'scheduleUpdate'])->name('wa.schedule.update');
    Route::delete('/wa-schedule/{id}', [WhatsAppController::class, 'scheduleDestroy'])->name('wa.schedule.destroy');
    Route::post('/wa-schedule/{id}/toggle', [WhatsAppController::class, 'scheduleToggle'])->name('wa.schedule.toggle');
    Route::post('/wa-schedule/{id}/send-now', [WhatsAppController::class, 'scheduleSendNow'])->name('wa.schedule.sendNow');
    Route::post('/wa-schedule/{id}/send-now-alias', [WhatsAppController::class, 'scheduleSendNow'])->name('wa.schedule.send-now');

    // WhatsApp Pimpinan
    Route::get('/wa-pimpinan', [WhatsAppController::class, 'pimpinanIndex'])->name('wa.pimpinan');
    Route::post('/wa-pimpinan', [WhatsAppController::class, 'pimpinanStore'])->name('wa.pimpinan.store');
    Route::put('/wa-pimpinan/{id}', [WhatsAppController::class, 'pimpinanUpdate'])->name('wa.pimpinan.update');
    Route::delete('/wa-pimpinan/{id}', [WhatsAppController::class, 'pimpinanDestroy'])->name('wa.pimpinan.destroy');

    // Activity Log
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

    // Profil
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});

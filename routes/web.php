<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardHarianController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\StoController;
use App\Http\Controllers\TeknisiController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TugasController;
use App\Http\Controllers\UploadController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-harian', [DashboardHarianController::class, 'index'])->name('dashboard.harian');
    Route::get('/dashboard-harian/data', [DashboardHarianController::class, 'data'])->name('dashboard.harian.data');

    // Tampilan mobile teknisi: Mulai -> Pre (material) -> Jalan -> Selesai
    Route::middleware('role:admin,hd,teknisi')->group(function () {
        Route::get('/tugas', [TugasController::class, 'index'])->name('tugas.index');
        Route::post('/tugas/{monitoring}/mulai', [TugasController::class, 'mulai'])->name('tugas.mulai');
        Route::get('/tugas/{monitoring}/pre', [TugasController::class, 'pre'])->name('tugas.pre');
        Route::post('/tugas/{monitoring}/pre', [TugasController::class, 'simpanPre'])->name('tugas.pre.simpan');
        Route::get('/tugas/{monitoring}/lokasi', [TugasController::class, 'lokasi'])->name('tugas.lokasi');
        Route::get('/tugas/{monitoring}/selesai', [TugasController::class, 'selesai'])->name('tugas.selesai');
        Route::post('/tugas/{monitoring}/selesai', [TugasController::class, 'simpanSelesai'])->name('tugas.selesai.simpan');
        Route::get('/foto/{foto}', [TugasController::class, 'foto'])->name('foto.tampil');
    });

    // Semua role boleh lihat data
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/{monitoring}', [MonitoringController::class, 'show'])->name('monitoring.show');

    // Edit Data (lengkapi kolom kosong, ubah status & memo): admin, HD, teknisi
    Route::middleware('role:admin,hd,teknisi')->group(function () {
        Route::put('/monitoring/{monitoring}', [MonitoringController::class, 'update'])
            ->name('monitoring.update');
    });

    // Admin & HD bisa mengirim tugas ke teknisi (tombol Send)
    Route::middleware('role:admin,hd')->group(function () {
        Route::post('/monitoring/{monitoring}/assign', [MonitoringController::class, 'assignTeknisi'])
            ->name('monitoring.assign');
    });

    // Khusus admin
    Route::middleware('role:admin')->group(function () {
        // Upload data: master, odp, kpro, bima (masing-masing sendiri, header divalidasi ketat)
        Route::get('/upload', [UploadController::class, 'index'])->name('monitoring.upload');
        Route::post('/upload/{jenis}/preview', [UploadController::class, 'preview'])->whereIn('jenis', ['master', 'odp', 'kpro', 'bima'])->name('upload.preview');
        Route::post('/upload/{batch}/proses', [UploadController::class, 'proses'])->whereNumber('batch')->name('upload.proses');
        Route::delete('/upload/{batch}', [UploadController::class, 'batal'])->whereNumber('batch')->name('upload.batal');

        // Master data
        Route::resource('sto', StoController::class)->except(['show']);
        Route::get('/odp/template', [OdpController::class, 'template'])->name('odp.template');
        Route::post('/odp/import', [OdpController::class, 'import'])->name('odp.import');
        Route::resource('odp', OdpController::class)->except(['show', 'create', 'edit']);
        Route::resource('teknisi', TeknisiController::class)->only(['index', 'show']);

        // Pengaturan
        Route::resource('user', UserController::class);
        Route::get('/session-log', [SessionController::class, 'index'])->name('session.index');
    });
});
<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PublicPage\LandingPage;
use App\Livewire\Auth\MultiLogin;
use App\Livewire\Admin;
use App\Livewire\Guru;
use App\Livewire\Siswa;

// Public
Route::get('/', LandingPage::class)->name('home');

// Auth
Route::get('/login', MultiLogin::class)->name('login')->middleware('login');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Export Jadwal (Global for Auth)
Route::middleware(['auth'])->prefix('export/jadwal')->group(function () {
    Route::get('/kelas', [\App\Http\Controllers\ExportJadwalController::class, 'exportKelas'])->name('export.kelas');
    Route::get('/guru', [\App\Http\Controllers\ExportJadwalController::class, 'exportGuru'])->name('export.guru');
    Route::get('/mapel', [\App\Http\Controllers\ExportJadwalController::class, 'exportMapel'])->name('export.mapel');
});

// Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', Admin\Dashboard::class)->name('admin.dashboard');
    Route::get('/guru', Admin\DataGuru::class)->name('admin.guru');
    Route::get('/siswa', Admin\DataSiswa::class)->name('admin.siswa');
    Route::get('/kelas', Admin\DataKelas::class)->name('admin.kelas');
    Route::get('/mapel', Admin\DataMapel::class)->name('admin.mapel');
    Route::get('/jurusan', Admin\DataJurusan::class)->name('admin.jurusan');
    Route::get('/kurikulum', Admin\DataKurikulum::class)->name('admin.kurikulum');

    Route::get('/jam-pelajaran', Admin\DataJamPelajaran::class)->name('admin.jam-pelajaran');
    Route::get('/generate', Admin\GenerateJadwal::class)->name('admin.generate');
    Route::get('/edit-jadwal', Admin\EditJadwal::class)->name('admin.edit-jadwal');
    Route::get('/export-jadwal', Admin\ExportJadwal::class)->name('admin.export-jadwal');
});

// Guru
Route::middleware(['auth', 'role:guru'])->prefix('guru')->group(function () {
    Route::get('/dashboard', Guru\Dashboard::class)->name('guru.dashboard');
    Route::get('/export-jadwal', Guru\ExportJadwal::class)->name('guru.export-jadwal');
});

// Siswa
Route::middleware(['auth', 'role:siswa'])->prefix('siswa')->group(function () {
    Route::get('/dashboard', Siswa\Dashboard::class)->name('siswa.dashboard');
    Route::get('/export-jadwal', Siswa\ExportJadwal::class)->name('siswa.export-jadwal');
});

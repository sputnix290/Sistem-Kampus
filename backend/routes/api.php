<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('/login', 'App\Http\Controllers\AuthController@login');
Route::post('/logout', 'App\Http\Controllers\AuthController@logout')->middleware('auth:sanctum');
Route::post('/register', 'App\Http\Controllers\AuthController@register');
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Protected routes with auth middleware
Route::middleware(['auth:sanctum'])->group(function () {
    // Mahasiswa routes (English: students)
    Route::apiResource('mahasiswa', 'App\Http\Controllers\MahasiswaController');
    
    // Dosen routes (English: lecturers)  
    Route::apiResource('dosen', 'App\Http\Controllers\DosenController');
    
    // Fakultas routes (English: faculties)
    Route::apiResource('fakultas', 'App\Http\Controllers\FakultasController');
    
    // Program studi routes (English: study programs)
    Route::apiResource('program-studi', 'App\Http\Controllers\ProgramStudiController');
    
    // Mata kuliah routes (English: courses)
    Route::apiResource('mata-kuliah', 'App\Http\Controllers\MataKuliahController');
    
    // KRS routes (English: enrollments)
    Route::apiResource('krs', 'App\Http\Controllers\KrsController');
    
    // Nilai routes (English: grades)
    Route::apiResource('nilai', 'App\Http\Controllers\NilaiController');
    
    // Presensi routes (English: attendance)
    Route::apiResource('presensi', 'App\Http\Controllers\PresensiController');
    
    // Pembayaran routes (English: payments)
    Route::apiResource('pembayaran', 'App\Http\Controllers\PembayaranController');
    
    // Pengumuman routes (English: announcements)
    Route::apiResource('pengumuman', 'App\Http\Controllers\PengumumanController');
    
    // Berita routes (English: news)
    Route::apiResource('berita', 'App\Http\Controllers\BeritaController');
    
    // Dashboard routes
    Route::get('/dashboard/stats', 'App\Http\Controllers\DashboardController@stats');
    Route::get('/dashboard/dosen/{id}', 'App\Http\Controllers\DashboardController@lecturerStats');
    Route::get('/dashboard/mahasiswa/{id}', 'App\Http\Controllers\DashboardController@studentDashboard');
    Route::get('/dashboard/fakultas/{id}', 'App\Http\Controllers\DashboardController@facultyAnalytics');
    Route::get('/dashboard/notifikasi/{id}', 'App\Http\Controllers\DashboardController@notifications');
    Route::get('/dashboard/trend', 'App\Http\Controllers\DashboardController@trendData');
    Route::get('/dashboard/perbandingan', 'App\Http\Controllers\DashboardController@departmentComparison');
    Route::get('/dashboard/statistik-cepat', 'App\Http\Controllers\DashboardController@quickStats');
    
    // Kalender akademik routes
    Route::apiResource('kalender', 'App\Http\Controllers\KalenderController');
    
    // FAQ routes
    Route::apiResource('faq', 'App\Http\Controllers\FaqController');
    
    // Kontak routes
    Route::apiResource('kontak', 'App\Http\Controllers\KontakController');
    
    // Export routes
    Route::post('/export/mahasiswa', 'App\Http\Controllers\ExportController@mahasiswa');
    Route::post('/export/pembayaran', 'App\Http\Controllers\ExportController@pembayaran');
});
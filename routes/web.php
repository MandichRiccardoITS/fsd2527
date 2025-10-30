<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\DebugController;

Route::get('/', [CalendarioController::class, 'index'])->name('calendario.index');
Route::get('/import', [CalendarioController::class, 'showImport'])->name('calendario.import');
Route::post('/update', [CalendarioController::class, 'scrapeAndUpdate'])->name('calendario.update');
Route::get('/export/ics', [CalendarioController::class, 'exportIcs'])->name('calendario.export.ics');

Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
Route::post('/logs/clear', [LogController::class, 'clear'])->name('logs.clear');

Route::get('/debug-site', [DebugController::class, 'analyzeSite'])->name('debug.site');
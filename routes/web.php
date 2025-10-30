<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarioController;

Route::get('/', [CalendarioController::class, 'index'])->name('calendario.index');
Route::get('/update', [CalendarioController::class, 'scrapeAndUpdate'])->name('calendario.update');


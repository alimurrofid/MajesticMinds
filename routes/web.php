<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\criteria_controller;
use App\Http\Controllers\AlternatifController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\PerhitunganController;
use App\Http\Controllers\HomeController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::prefix('/')->group (function () {
    // Route::get('/', function () {
    //     return view('dashboard.home');
    // })->name('dashboard.home');

    // Route::get('/criteria', function () {
    //     return view('dashboard.criteria');
    // })->name('dashboard.criteria');
    Route::resource('criteria', criteria_controller::class);
    Route::resource('alternatif', AlternatifController::class);
    Route::resource('penilaian', PenilaianController::class);
    Route::resource('perhitungan', PerhitunganController::class);
    Route::get('/',[HomeController::class, 'index'])->name('home.index');
    // Route::get('/alternatif', function () {
    //     return view('dashboard.alternatif');
    // })->name('dashboard.alternatif');
});

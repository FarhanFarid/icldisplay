<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DisplayController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::group(['prefix' => 'display'], function () {
    Route::get('/', [DisplayController::class, 'index'])->name('display.index');
    Route::get('/geticllisting', [DisplayController::class, 'getIclListing'])->name('display.geticllisting');
});

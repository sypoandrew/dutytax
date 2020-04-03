<?php

use Illuminate\Support\Facades\Route;
use Sypo\Dutytax\Http\Controllers\ModuleController;

Route::get('dutytax', [ModuleController::class, 'index'])->name('admin.modules.dutytax');
Route::post('dutytax', [ModuleController::class, 'update'])->name('admin.modules.dutytax');
Route::get('dutytax/calculate', [ModuleController::class, 'calculate'])->name('admin.modules.calculate.calculate');

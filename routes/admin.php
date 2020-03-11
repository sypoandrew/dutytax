<?php

use Illuminate\Support\Facades\Route;
use Sypo\Dutytax\Http\Controllers\ModulesController;

Route::get('dutytax', [ModulesController::class, 'index'])->name('admin.modules.dutytax');
Route::post('dutytax', [ModulesController::class, 'update'])->name('admin.modules.dutytax');

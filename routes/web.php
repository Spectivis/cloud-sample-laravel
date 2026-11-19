<?php

use App\Http\Controllers\EntryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EntryController::class, 'index'])->name('guestbook');
Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');

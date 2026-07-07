<?php

declare(strict_types=1);

use App\Http\Controllers\Note\StoreNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::post('/notes', StoreNoteController::class)->name('notes.store');
});

<?php

use App\Http\Controllers\Result\CompetitionResultController;
use App\Http\Controllers\Result\ResultPhotoController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.result'))->name('result.')->group(function () {
    Route::get('/', [CompetitionResultController::class, 'index'])->name('index');
    Route::get('yarismalar/{competition}', [CompetitionResultController::class, 'show'])->name('competitions.show');
    Route::get('fotograflar/{submissionPhoto}', ResultPhotoController::class)->name('photos.show');
});

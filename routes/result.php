<?php

use App\Http\Controllers\Result\CompetitionResultController;
use App\Http\Controllers\Result\ResultPhotoController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.result'))->name('result.')->group(function () {
    Route::get('/', [CompetitionResultController::class, 'index'])->middleware('throttle:120,1')->name('index');
    Route::get('yarismalar/{competition}', [CompetitionResultController::class, 'show'])->middleware('throttle:120,1')->name('competitions.show');
    Route::get('yayinlar/{publication}/fotograflar/{photoId}', ResultPhotoController::class)->middleware('throttle:180,1')->name('publications.photos.show');
    Route::get('fotograflar/{submissionPhoto}', ResultPhotoController::class)->middleware('throttle:180,1')->name('photos.show');
});

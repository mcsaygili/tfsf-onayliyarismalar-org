<?php

use App\Http\Controllers\PublicSite\CompetitionController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\PublicSite\SitemapController;
use App\Http\Controllers\SetLanguageController;
use Illuminate\Support\Facades\Route;

Route::domain(config('domains.base'))->name('public.')->group(function () {
    Route::get('language/{locale}', SetLanguageController::class)->name('language');
    Route::get('/', HomeController::class)->name('home');
    Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('yarismalar', [CompetitionController::class, 'index'])->name('competitions.index');
    Route::get('yarismalar/{competition:public_slug}', [CompetitionController::class, 'show'])->name('competitions.show');
});

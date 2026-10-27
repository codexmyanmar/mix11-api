<?php

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;

// No need for Route::group, Filament handles it!
Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'my'])) {
        Session::put('locale', $locale);
        Session::save();
    }
    return back();
})->name('switch-language');
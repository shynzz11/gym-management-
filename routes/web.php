<?php

use App\Http\Controllers\Guest\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.login'))->name('home');

Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/privacy-policy', [App\Http\Controllers\Guest\LegalPageController::class, 'show'])->defaults('slug', 'privacy-policy')->name('legal.privacy');
Route::get('/terms-and-conditions', [App\Http\Controllers\Guest\LegalPageController::class, 'show'])->defaults('slug', 'terms-and-conditions')->name('legal.terms');
Route::get('/about-us', [App\Http\Controllers\Guest\LegalPageController::class, 'show'])->defaults('slug', 'about-us')->name('legal.about');
Route::get('/contact-us', [App\Http\Controllers\Guest\ContactController::class, 'show'])->name('contact');

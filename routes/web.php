<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/hello', fn () => 'Hello, World！')->name('hello');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/hello', 'pages::hello')->name('hello');
});

require __DIR__.'/settings.php';

<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin')->name('home');

require __DIR__.'/admin.php';

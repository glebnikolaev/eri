<?php

use Illuminate\Support\Facades\Route;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/houses', function () {
    return view('houses');
});
Route::get('/plates', function () {
    return view('plates');
});

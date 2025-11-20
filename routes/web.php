<?php

use App\Http\Controllers\BooksController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->middleware('api')->group(function () {
    Route::get('get-categories', [BooksController::class, 'getCategories']);
    Route::get('get-books', [BooksController::class, 'getBooks']);
});

Route::get('/{any}', function () {
    return view('welcome'); // or whatever file loads your Vue app
})->where('any', '.*');

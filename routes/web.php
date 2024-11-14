<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/register', fn() => abort(404));
Route::post('/register', fn() => abort(404));

Route::get('/', function () {
    return redirect()->route('questions');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('questions');
    })->name('dashboard');

    Route::get('/questions', function () {
        return view('list-all-questions');
    })->name('questions');

    Route::get('/questions/{question_id}/votes', function ($question_id) {
        return view('list-results', [
            'question_id' => $question_id,
        ]);
    })->name('results');
});
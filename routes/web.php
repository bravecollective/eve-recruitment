<?php

// Base home route
Route::get('/', 'HomeController@home')->name('home');

// Authentication routes
Route::get('login', 'Auth\AuthController@redirectToProvider')->name('login');
Route::get('login/callback', 'Auth\AuthController@handleProviderCallback')->name('loginCallback');
Route::get('logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

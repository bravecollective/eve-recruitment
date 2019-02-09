<?php

// Base home route
Route::get('/', 'HomeController@home')->name('home');

// Corp ad routes
Route::get('corp/ad', 'CorpAdController@manageAd');
Route::post('corp/ad/save', 'CorpAdController@saveAd');

// Authentication routes
Route::get('login', 'Auth\AuthController@redirectToProvider')->name('login');
Route::get('login/callback', 'Auth\AuthController@handleProviderCallback')->name('loginCallback');
Route::get('logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

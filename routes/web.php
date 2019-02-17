<?php

// Base home route
Route::get('/', 'HomeController@home')->name('home');

// Corp members routes
Route::get('/corporations/{corp_id}', 'CorpMembersController@viewCorpMembers');

// Corp ad routes
Route::get('/corporations/{id}/ad', 'CorpAdController@manageAd');
Route::post('/corporations/{id}/ad/save', 'CorpAdController@saveAd');

// Group Ads
Route::get('/group/ads', 'GroupAdController@listAds');
Route::get('/group/ad/create', 'GroupAdController@createAd');
Route::get('/group/ad/{id}', 'GroupAdController@manageAd');
Route::post('/group/ad/save', 'GroupAdController@saveAd');

// Ajax routes
Route::post('/api/character/search', 'SearchController@characterSearch');
Route::post('/api/character/roles', 'PermissionsController@loadUserRoles');
Route::post('/api/character/roles/save', 'PermissionsController@saveUserRoles');
Route::post('/api/admin/roles/auto/save', 'PermissionsController@saveAutoRoles');
Route::get('/api/requirements/template', 'RecruitmentRequirementController@getTemplate');

// Global admin routes
Route::get('/admin/roles', 'PermissionsController@globalRoles');
Route::get('/admin/roles/auto', 'PermissionsController@autoRoles');
Route::get('/admin/coregroups', 'PermissionsController@listCoreGroups');

// Authentication routes
Route::get('/login', 'Auth\AuthController@redirectToProvider')->name('login');
Route::get('/login/callback', 'Auth\AuthController@handleProviderCallback')->name('loginCallback');
Route::get('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

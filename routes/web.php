<?php

// Base home route
Route::get('/', 'HomeController@home')->name('home');

// Corp members routes
Route::get('/corporations/{corp_id}', 'CorpMembersController@viewCorpMembers');

// Corp ad routes
Route::get('/corporations/{id}/ad', 'CorpAdController@manageAd');
Route::post('/corporations/{id}/ad/save', 'CorpAdController@saveAd');
Route::get('/recruitments/{id}/delete', 'CorpAdController@deleteAd');

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
Route::delete('/api/recruitments/{ad_id}/questions/{question_id}', 'GroupAdController@deleteQuestion');
Route::delete('/api/recruitments/{ad_id}/requirements/{requirement_id}', 'GroupAdController@deleteRequirement');

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

// Application routes
Route::get('/applications', 'ApplicationController@getAvailableApplications');
Route::get('/{slug}', 'ApplicationController@loadAdBySlug');
Route::post('/recruitments/{recruitment_id}/apply', 'ApplicationController@apply');
Route::get('/applications/{id}', 'ApplicationController@viewApplications');
Route::get('/application/{id}', 'ApplicationController@viewApplication');
Route::post('/application/{id}/state/update', 'ApplicationController@updateState');
Route::post('/application/{id}/comments/add', 'CommentController@addComment');
Route::post('/application/{id}/comments/delete', 'CommentController@deleteComment');
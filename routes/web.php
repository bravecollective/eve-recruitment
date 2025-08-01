<?php

// Base home route
Route::get('/', 'HomeController@home')->name('home');

// Corp members routes
Route::get('/corporations/{corp_id}', 'CorpMembersController@viewCorpMembers');

// Corp ad routes
Route::get('/corporations/{id}/ad', 'CorpAdController@manageAd');
Route::post('/corporations/{id}/ad/save', 'CorpAdController@saveAd');
Route::get('/corporations/manage/roles', 'CorpAdController@listCorpsForRoles');
Route::get('/corporations/{corp_id}/manage/roles', 'CorpAdController@manageRoles');
Route::get('/recruitments/{id}/delete', 'CorpAdController@deleteAd');

// Group Ads
Route::get('/group/ads', 'GroupAdController@listAds');
Route::get('/group/ad/create', 'GroupAdController@createAd');
Route::get('/group/ad/{id}', 'GroupAdController@manageAd');
Route::post('/group/ad/save', 'GroupAdController@saveAd');
Route::get('/group/permissions', 'GroupAdController@listAdsForPermissions');
Route::get('/group/ad/{id}/permissions', 'GroupAdController@groupPermissions');

// Ajax routes
Route::post('/api/character/search', 'SearchController@characterSearch');
Route::post('/character/search', 'SearchController@navbarCharacterSearch');
Route::post('/api/character/roles', 'PermissionsController@loadUserRoles');
Route::post('/api/character/roles/save', 'PermissionsController@saveUserRoles');
Route::post('/api/admin/roles/auto/save', 'PermissionsController@saveAutoRoles');
Route::get('/api/{type}/{id}/requirements/template', 'RecruitmentRequirementController@getTemplate');
Route::get('/api/{type}/{id}/requirements/template/{requirement_id}', 'RecruitmentRequirementController@getTemplateWithID');
Route::get('/api/auto_roles/template', 'PermissionsController@getAutoRoleTemplate');
Route::post('/api/auto_roles/delete', 'PermissionsController@deleteAutoRole');
Route::delete('/api/recruitments/{ad_id}/questions/{question_id}', 'GroupAdController@deleteQuestion');
Route::delete('/api/recruitments/{ad_id}/requirements/{requirement_id}', 'GroupAdController@deleteRequirement');
Route::post('/api/groups/roles', 'GroupAdController@loadPermissions');
Route::post('/api/groups/roles/save', 'GroupAdController@savePermissions');
Route::post('/api/corporations/roles', 'CorpAdController@loadPermissions');
Route::post('/api/corporations/roles/save', 'CorpAdController@savePermissions');

// Global admin routes
Route::get('/admin/roles', 'PermissionsController@globalRoles');
Route::get('/admin/roles/auto', 'PermissionsController@autoRoles');
Route::get('/admin/coregroups', 'PermissionsController@listCoreGroups');
Route::get('/admin/generator', 'ApplicationController@applicationGenerator');
Route::post('/admin/generator/save', 'ApplicationController@createApplication');

// Authentication routes
Route::get('/login', 'AuthController@redirectToProvider')->name('login');
Route::get('/login/callback', 'AuthController@handleProviderCallback')->name('loginCallback');
Route::get('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

// Application routes
Route::post('/recruitments/{recruitment_id}/apply', 'ApplicationController@apply');
Route::get('/applications/{id}', 'ApplicationController@viewApplications');
Route::get('/mail/{char_id}/{id}', 'ApplicationController@getSingleMail');
Route::get('/application/{id}', 'ApplicationController@viewApplication');
Route::post('/application/{id}/state/update', 'ApplicationController@updateState');
Route::post('/application/{id}/comments/add', 'CommentController@addComment');
Route::post('/application/{id}/comments/delete', 'CommentController@deleteComment');
Route::get('/application/{id}/warnings', 'ApplicationController@loadWarnings');
Route::get('/application/{id}/revoke', 'ApplicationController@revokeApplication');
Route::get('/character/{id}', 'ApplicationController@viewCharacterEsi');
Route::post('/applications/{id}', 'ApplicationController@loadAjaxApplications');
Route::get('/applications', 'ApplicationController@getAvailableApplications');
Route::get('/delete/application/{id}', 'ApplicationController@deleteApplication');

// Stats Routes
Route::get('/stats/{id}', 'StatsController@loadStatsPage');
Route::post('/stats', 'StatsController@lookupStats');

// ESI AJAX routes
Route::get('/api/esi/{char_id}/overview', 'ApplicationController@loadOverview');
Route::get('/api/esi/{char_id}/skills', 'ApplicationController@loadSkills');
Route::get('/api/esi/{char_id}/mail', 'ApplicationController@loadMail');
Route::get('/api/esi/{char_id}/assets', 'ApplicationController@loadAssets');
Route::get('/api/esi/{char_id}/journal', 'ApplicationController@loadJournal');
Route::get('/api/esi/{char_id}/market', 'ApplicationController@loadMarket');
Route::get('/api/esi/{char_id}/notifications', 'ApplicationController@loadNotifications');
Route::get('/api/esi/{char_id}/killmails', 'ApplicationController@loadKillmails');
Route::get('/api/esi/{char_id}/contracts', 'ApplicationController@loadContracts');
Route::post('/api/esi/{char_id}/fit_check', 'ApplicationController@checkFit');
Route::post('/api/esi/{char_id}/skillplan_check', 'ApplicationController@checkSkillplan');
Route::post('/api/esi/{char_id}/assets_check', 'ApplicationController@checkAssets');

// Recruitment ad
Route::get('/{slug}', 'ApplicationController@loadAdBySlug');

// misc
Route::post('/character/add', 'CharacterController@addCharacter');

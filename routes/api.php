<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/user/phone/verify','Api\AuthController@verifyUserPhone')->name('user.free.signin');
Route::post('/user/otp/verify', 'Api\AuthController@verifyUserOTP')->name('user.verify_pin');

Route::get('/user/countries', 'Api\CountriesController@index');
Route::get('/user/image/{id}', 'Api\SystemImageController@showImage');

Route::middleware(
	['user.access_token', 'user.session_id', 'user.onesignal.player_id', 'user.disabled_guard']
)->group(function () {
	Route::get('/user/signout', 'Api\AuthController@logoutUser')->name('user.logout');
	Route::get('/user/pin/resend', 'Api\AuthController@resendUserPin')->name('user.resend_pin');
	
    Route::get('/user/profile', 'Api\AuthController@userProfile')->name('user.profile');
	Route::post('/user/update', 'Api\AuthController@updateUserProfile')->name('user.profile_update');

});


Route::post('/tutor/phone/verify','Api\Auth\AuthController@verifyTutorPhone')->name('tutor.free.signin');
Route::post('/tutor/otp/verify', 'Api\Auth\AuthController@verifyTutorOTP')->name('tutor.verify_pin');
Route::get('/tutor/pin/resend', 'Api\Auth\AuthController@resendTutorPin')->name('tutor.resend_pin');

Route::get('/tutor/countries', 'Api\CountriesController@index');

// Route::middleware(
// 	['tutor.access_token', 'tutor.session_id', 'tutor.onesignal.player_id', 'tutor.disabled_guard']
// )->group(function () {
// 	Route::get('/tutor/signout', 'Api\AuthController@logoutUser')->name('tutor.logout');


//     Route::get('/tutor/profile', 'Api\AuthController@userProfile')->name('tutor.profile');
// 	Route::post('/tutor/update', 'Api\AuthController@updateUserProfile')->name('tutor.profile_update');
// });

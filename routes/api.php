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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/checkIfPdfSignature', 'ApiController@checkIfPdfSignature');

Route::post('/processPDF', 'ApiController@processPDF');
Route::post('/parseArguments', 'ApiController@parseArgsAndSplit');
Route::post('/parseEachImage', 'ApiController@parseEachImage');
Route::post('/searchClient', 'ApiController@searchClient');
Route::post('/cleanServerFiles', 'ApiController@cleanServerFiles');
Route::get('/getVLAEmail', 'ApiController@getVLAEmail');
Route::post('/setEmailAttachment', 'ApiController@setEmailAttachment');




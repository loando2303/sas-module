<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

Route::group(['prefix' => 'sas'], function () {
    Route::get('/dropdown/get-all', 'API\SurveyDropdownController@getAllDropdowns')->name('sas.api.get_all_dropdowns');
    Route::middleware(['check_api','log_request'])->group(function () {
        Route::post('upload-data', 'API\SurveyUploadController@uploadData');
        Route::post('upload-manifest', 'API\SurveyUploadController@uploadManifest');
        Route::post('upload-image', 'API\SurveyUploadController@uploadImage');
        Route::post('change-surveyor', 'API\SurveyController@changeSurveyor');
    });
});
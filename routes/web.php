<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MutationsController;
use App\Http\Controllers\Auth\LoginController;

Route::group(['middleware' => 'auth'], static function () {
    Route::get('/', [PagesController::class, 'showApp'])->name('home');

    Route::group(['prefix' => 'assets'], static function () {
        Route::post('/', [AssetsController::class, 'getAssetInfo']);
        Route::post('upload', [AssetsController::class, 'uploadAsset']);
        Route::post('delete', [AssetsController::class, 'deleteAsset']);
        Route::post('contents/zip/single', [AssetsController::class, 'getFileContentsInsideZipArchive']);
    });

    Route::group(['prefix' => 'projects'], static function () {
        Route::post('/', [ProjectsController::class, 'getProjects']);
        Route::post('create', [ProjectsController::class, 'createProject']);
        Route::post('rename', [ProjectsController::class, 'renameProject']);
        Route::post('build', [ProjectsController::class, 'buildProject']);
        Route::post('clone', [ProjectsController::class, 'cloneProject']);
        Route::post('pay', [ProjectsController::class, 'getPayLink']);
        Route::post('{uuid}', [ProjectsController::class, 'getProject']);
        Route::delete('{uuid}', [ProjectsController::class, 'deleteProject']);
    });

    Route::group(['prefix' => 'mutations'], static function () {
        // Mutations Viewer
        Route::group(['prefix' => 'debug'], static function () {
            Route::post('/', [MutationsController::class, 'getMutationsForDebugging']);
            Route::delete('{path}', [MutationsController::class, 'deleteMutationDBG'])->where('path', '.*');
        });

        Route::post('/', [MutationsController::class, 'mutate']);
        Route::post('/delete/bulk/{projectId}', [MutationsController::class, 'bulkDeleteMutations']);
        Route::delete('/{projectId}/{path}', [MutationsController::class, 'deleteMutation'])->where('path', '.*');

        Route::group(['prefix' => 'fetch'], static function () {
            Route::post('single', [MutationsController::class, 'getMutation']);
            Route::post('batch', [MutationsController::class, 'getMutations']);
        });
    });

    Route::group(['prefix' => 'settings'], static function () {
        Route::post('/', [SettingsController::class, 'saveSetting']);
        Route::post('get', [SettingsController::class, 'getSetting']);

        Route::group(['prefix' => 'project'], static function () {
            Route::post('/', [SettingsController::class, 'saveProjectSetting']);
            Route::post('get', [SettingsController::class, 'getProjectSetting']);
        });
    });

    Route::group(['prefix' => 'summary'], static function () {
        Route::post('/', [SummaryController::class, 'getSummary']);
    });
});

Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], static function () {
    Route::get('login', [LoginController::class, 'showLoginPage'])->name('login');
    Route::get('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('login/github', [LoginController::class, 'redirectToProvider'])->name('redirect.to.github');
    Route::get('login/github/authorize', [LoginController::class, 'authorizeUsingGitHub']);
});

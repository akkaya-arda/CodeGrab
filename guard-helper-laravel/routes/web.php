<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\InstallController;

Route::get('/', function () {
    if (!file_exists(storage_path('installed'))) {
        return redirect('/install');
    }

    return redirect('/grab-code');
});

Route::get('/install', [InstallController::class, 'index']);
Route::post('/install/check', [InstallController::class, 'checkRequirements']);
Route::post('/install/database', [InstallController::class, 'configureDatabase']);
Route::post('/install/run', [InstallController::class, 'runInstallation']);

Route::get('/{any}', function () {
    if (!file_exists(storage_path('installed'))) {
        return redirect('/install');
    }
    return view()->exists('app') ? view('app') : abort(404);
})->where('any', '^(?!api|install|up).*$');

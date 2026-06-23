<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/web/{any?}', function () {
    $path = public_path('web/index.html');
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
})->where('any', '.*');

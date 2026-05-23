<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Swagger UI — single source of API docs
|--------------------------------------------------------------------------
| Loads the OpenAPI spec from qbazaar-contracts/openapi/v1.yaml (served by
| /api/v1/openapi.yaml) and renders Swagger UI from CDN.
| Available at /swagger and the canonical /docs.
*/
Route::view('/swagger', 'swagger')->name('swagger.ui');
Route::view('/docs', 'swagger')->name('docs');

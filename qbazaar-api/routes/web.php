<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Swagger UI — Interactive API explorer
|--------------------------------------------------------------------------
| Loads the OpenAPI spec from qbazaar-contracts/openapi/v1.yaml (served by
| /api/v1/openapi.yaml) and renders Swagger UI from CDN. Available at /swagger.
|
| Scribe docs (/docs) stay separate — those are auto-derived from PHPDoc as
| backup documentation. The contract spec is the single source of truth.
*/
Route::get('/swagger', function () {
    return view('swagger');
})->name('swagger.ui');

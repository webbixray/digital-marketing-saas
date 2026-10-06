<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
|| API Documentation Routes (Rate Limited)
||--------------------------------------------------------------------------
||
|| These routes serve the API documentation and OpenAPI specification.
|| They are publicly accessible so developers can review the API
|| without needing an API token.
|| Rate limited to prevent abuse.
||
*/

// Swagger UI - Interactive API documentation
Route::get('/api/docs', [DocsController::class, 'swaggerUi'])->middleware('throttle:60,1')->name('api.docs');

// OpenAPI specification - YAML format
Route::get('/api/docs/openapi.yaml', [DocsController::class, 'openapiYaml'])->middleware('throttle:30,1')->name('api.openapi.yaml');

// OpenAPI specification - JSON format
Route::get('/api/docs/openapi.json', [DocsController::class, 'openapiJson'])->middleware('throttle:30,1')->name('api.openapi.json');

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
| Load the routes files in the "routes" directory.
|
*/

// Horizon dashboard (admin only)
Route::prefix('horizon')->middleware(['auth', 'agency', 'role:owner|admin'])->group(function () {
    // Laravel Horizon web dashboard
    \Laravel\Horizon\Http\Controllers\DashboardController::class;
});

// Route::fallback(function () {
//     return response()->json(['message' => 'Not Found'], 404);
// });

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\userAccessController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas de autenticación
Route::group([
    'prefix' => 'auth',
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->name('me');
});

// Rutas protegidas
Route::group([
    //'middleware' => 'auth:api', 
], function ($router) {
    
    // IMPORTANTE: La ruta config DEBE ir ANTES del resource
    // para que no sea capturada por el show() del resource
    Route::get("users/config", [UserAccessController::class, 'config']);
    
    // Ruta para actualizar con FormData (imagen)
    Route::post('/users/{id}', [UserAccessController::class, 'update']);
    
    // Resource de usuarios
    Route::resource("users", userAccessController::class);
    
    // Resource de roles
    Route::resource("roles", RolePermissionController::class);
});

// Ruta de Sanctum (debe ir AL FINAL para no interferir)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
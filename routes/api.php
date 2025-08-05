<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClassroomController;
use App\Http\Controllers\API\EkskulController;
use App\Http\Controllers\API\SertifikationController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\StudiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('studi', StudiController::class)->only(['index', 'show']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('classrooms', ClassroomController::class);
    Route::get('classrooms/jenjang/{nama_studi}', [ClassroomController::class, 'KelasByJenjang']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('ekskul', EkskulController::class);
    Route::get('ekskul/jenjang/{nama_studi}', [EkskulController::class, 'EkskulByJenjang']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('students', StudentController::class);
    Route::post('students/import', [StudentController::class, 'importExcel']);
    Route::get('students/classroom/{classroom_id}', [StudentController::class, 'byClassroom']);
    Route::get('students/ekskul/{ekskul_id}', [StudentController::class, 'byEkskul']);
});
Route::middleware('auth:sanctum')->group(function() {
    Route::apiResource('sertifikation', SertifikationController::class);
});

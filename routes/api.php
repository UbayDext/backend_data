<?php
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClassroomController;
use App\Http\Controllers\API\EkskulAttendancesController;
use App\Http\Controllers\API\EkskulController;
use App\Http\Controllers\API\IndividuRaceController;
use App\Http\Controllers\API\IndividuRaceParticipansController;
use App\Http\Controllers\API\LombadController;
use App\Http\Controllers\API\SertifikationController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\TeamRaceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\StudiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('studi', StudiController::class)->only(['index', 'show']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('classrooms', ClassroomController::class);
    Route::get('classrooms/jenjang/{nama_studi}', [ClassroomController::class, 'KelasByJenjang']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('ekskul/options', [EkskulController::class, 'options']);
    Route::apiResource('ekskul', EkskulController::class);

    Route::get('ekskul/jenjang/{nama_studi}', [EkskulController::class, 'EkskulByJenjang']);
});
Route::middleware('auth:sanctum')->group(function () {
    // Import
    Route::post('students/import', [StudentController::class, 'importExcel']);
    Route::post('students/import_many', [StudentController::class, 'importMany']);

    // Filter
    Route::get('students/classroom/{classroom_id}', [StudentController::class, 'byClassroom']);
    Route::get('students/ekskul/{ekskul_id}', [StudentController::class, 'byEkskul']);
    Route::get('/students/with-sertifikat', [StudentController::class, 'withSertifikat']);



    // CRUD
    Route::apiResource('students', StudentController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('sertifikations', SertifikationController::class);
    Route::get('sertifikations/by-student/{student}', [SertifikationController::class, 'byStudent']);
    Route::get('sertifikations-counts', [SertifikationController::class, 'counts']);
});
Route::middleware('auth:sanctum', 'restrict.ekskul')->group(function() {
    Route::get('ekskul-attendance/daily', [EkskulAttendancesController::class, 'dailyAll']);
    Route::post('ekskul-attendance/update', [EkskulAttendancesController::class, 'updateOrCreate']);
    Route::get('ekskul-attendance/rekap', [EkskulAttendancesController::class, 'rekap']);
});
Route::middleware('auth:sanctum')->group(function() {
    Route::apiResource('lombads', LombadController::class);
});
Route::middleware('auth:sanctum')->group(function() {
    Route::apiResource('individurace', IndividuRaceController::class);
    Route::get('individurace/{race}/candidates',   [IndividuRaceParticipansController::class,'candidates']);
    Route::get('individurace/{race}/participants', [IndividuRaceParticipansController::class,'index']);
    Route::post('individurace/{race}/participants',[IndividuRaceParticipansController::class,'store']);
    Route::put('individurace/{race}/participants/{participant}',   [IndividuRaceParticipansController::class,'update']);
    Route::post('individurace/{race}/participants/bulk-scores',    [IndividuRaceParticipansController::class,'bulkScores']);
    Route::delete('individurace/{race}/participants/{participant}',[IndividuRaceParticipansController::class,'destroy']);
});
Route::middleware('auth:sanctum')->group(function() {
    Route::get('/lomba/{lombad_id}/team-race', [TeamRaceController::class, 'index']);
    Route::get('team-race/{id}', [TeamRaceController::class, 'show']);
    Route::post('team-races', [TeamRaceController::class, 'store']);
    Route::put('/team-races/{id}/set-champion', [TeamRaceController::class, 'setChampion']);
    Route::post('/team-races/{id}/set-winner-match1', [TeamRaceController::class, 'setWinnerMatch1']);
    Route::post('/team-races/{id}/set-winner-match2', [TeamRaceController::class, 'setWinnerMatch2']);
    Route::delete('team-race/{id}', [TeamRaceController::class, 'destroy']);
    Route::put('/team-races/{id}', [TeamRaceController::class, 'update']);
});
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me',         [UserController::class, 'me'])->name('me.show');
    Route::get('/me/basic',   [UserController::class, 'meBasic'])->name('me.basic');
    Route::put('/me/profile', [UserController::class, 'updateMe']);
    Route::put('/me/password',[UserController::class, 'changeMyPassword'])->name('me.password');

    Route::get   ('/users',              [UserController::class, 'index']);
    Route::post  ('/users',              [UserController::class, 'store']);
    Route::get   ('/users/{user}',       [UserController::class, 'show'])->whereNumber('user');
    Route::put   ('/users/{user}',       [UserController::class, 'update'])->whereNumber('user');
    Route::patch ('/users/{user}',       [UserController::class, 'update'])->whereNumber('user');
    Route::delete('/users/{user}',       [UserController::class, 'destroy'])->whereNumber('user');
    Route::put   ('/users/{user}/password',[UserController::class, 'changePassword'])->whereNumber('user');
});


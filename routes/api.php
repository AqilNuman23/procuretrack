<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ['no' => $request->user()->USNo, 'name' => $request->user()->USName, 'worker_id' => $request->user()->USWorkerID];
})->middleware(['auth:sanctum', 'foundation.active']);

Route::get('/v1/status', function () {
    return response()->json([
        'message' => 'ProcureTrack API is connected!',
    ]);
});

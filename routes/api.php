<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TaskController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login',[AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

Route::post('/tasks/add_task',[TaskController::class, 'AddTask']);
Route::put('/tasks/{id}/update_task',[TaskController::class, 'UpdateTask']);

Route::post('/tasks/load_tasks',[TaskController::class, 'LoadTasksWithFilter']);
Route::post('/tasks/add_task_dependent',[TaskController::class, 'AddTaskDependency']);
Route::get('/tasks/{id}/with-dependencies', [TaskController::class, 'loadTaskWithDependencies']);

});
Route::fallback(function () {
    return response()->json(['message' => 'API route not found'], 404);
});

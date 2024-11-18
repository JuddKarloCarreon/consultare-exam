<?php

use App\Http\Controllers\TasksController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::controller(TasksController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/task/create', 'create');
    Route::get('/task/read/{status}', 'read');
    Route::patch('/task/{id}/update-status', 'updateStatus');
    Route::patch('/task/{id}/update', 'update');
    Route::delete('/task/{id}', 'delete');
});

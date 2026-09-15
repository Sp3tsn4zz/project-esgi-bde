<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// --- Authentification (page publique) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Pages protégées : réservées aux membres du BDE connectés ---
Route::middleware('auth')->group(function () {

    Route::get('/', [PlanningController::class, 'index'])->name('planning');
    Route::get('/planning/events.json', [PlanningController::class, 'eventsJson'])->name('planning.events.json');

    // Événements
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events.json', [EventController::class, 'json'])->name('events.json.list');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::patch('/events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
    Route::get('/events/{event}/json', [EventController::class, 'showJson'])->name('events.json');
    Route::get('/events/{event}/export', [EventController::class, 'export'])->name('events.export');
    Route::post('/events/{event}/participants', [EventController::class, 'addParticipant'])->name('events.participants.add');
    Route::delete('/events/{event}/participants/{student}', [EventController::class, 'removeParticipant'])->name('events.participants.remove');

    // Étudiants
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students.json', [StudentController::class, 'json'])->name('students.json');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
});

<?php

use App\Http\Controllers\Admin\ProfitLossPrintController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Learn\LearnController;
use App\Http\Controllers\Site\CertificateController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\InstructorController;
use App\Livewire\Checkout;
use App\Livewire\LessonPlayer;
use App\Livewire\MyTasks;
use App\Livewire\TakeQuiz;
use Illuminate\Support\Facades\Route;

// ---- Public site -------------------------------------------------------------
Route::get('/', HomeController::class)->name('home');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])->name('instructors.show');
Route::view('/register', 'site.register')->name('register');
Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');

// ---- Student authentication ---------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Student portal -----------------------------------------------------------
Route::middleware('auth')->prefix('learn')->name('learn.')->group(function () {
    Route::get('/', [LearnController::class, 'dashboard'])->name('dashboard');
    Route::get('/courses', [LearnController::class, 'courses'])->name('courses');
    Route::get('/courses/{course}', [LearnController::class, 'course'])->name('course');
    Route::get('/courses/{course}/lessons/{lesson}', LessonPlayer::class)->name('lesson');
    Route::get('/courses/{course}/quiz', TakeQuiz::class)->name('quiz');
    Route::get('/tasks', MyTasks::class)->name('tasks');
    Route::get('/certificates', [LearnController::class, 'certificates'])->name('certificates');
    Route::get('/checkout/{course}', Checkout::class)->name('checkout');
});

// ---- Back-office printables (Super Admin) ---------------------------------------
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/admin/reports/pl/print', ProfitLossPrintController::class)->name('admin.reports.pl.print');
});

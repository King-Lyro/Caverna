<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreedingController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\CricketLedgerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\MemorialController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffApplicationController;
use App\Http\Controllers\StaffCharacterController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home', ['title' => 'Home']);
})->name('home');

Route::get('/forum', [ForumController::class, 'index'])->name('forum');
Route::get('/forum/boards/{board:slug}', [ForumController::class, 'board'])->name('forum.board');
Route::get('/forum/threads/{thread:slug}', [ForumController::class, 'thread'])->name('forum.thread');
Route::get('/characters', [CharacterController::class, 'index'])->name('characters');
Route::get('/characters/memorial', [MemorialController::class, 'index'])->name('characters.memorial');
Route::get('/activity', [ActivityController::class, 'index'])->name('activity');
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/breeding', [BreedingController::class, 'create'])->middleware('auth')->name('breeding.create');
Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');
Route::get('/crickets', [CricketLedgerController::class, 'index'])->middleware('auth')->name('crickets');
Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth')->name('notifications');
Route::get('/account', [AccountController::class, 'edit'])->middleware('auth')->name('account.edit');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('forum')->name('forum.')->group(function () {
    Route::get('/boards/{board:slug}/threads/create', [ForumController::class, 'createThread'])->name('thread.create');
    Route::post('/boards/{board:slug}/threads', [ForumController::class, 'storeThread'])->name('thread.store');
    Route::post('/threads/{thread:slug}/replies', [ForumController::class, 'storeReply'])->name('reply.store');
    Route::post('/posts/{post}/report', [ModerationController::class, 'reportPost'])->middleware('throttle:10,1')->name('post.report');
});

Route::middleware('auth')->group(function () {
    Route::get('/characters/create', [CharacterController::class, 'create'])->name('characters.create');
    Route::post('/characters', [CharacterController::class, 'store'])->middleware('throttle:10,1')->name('characters.store');
    Route::post('/breeding', [BreedingController::class, 'store'])->middleware('throttle:10,1')->name('breeding.store');
    Route::post('/pregnancies/{pregnancy}/birth', [BreedingController::class, 'birth'])->middleware('throttle:10,1')->name('breeding.birth');
    Route::post('/characters/{character}/mate-request', [CharacterController::class, 'requestMate'])->middleware('throttle:10,1')->name('characters.mate-request');
    Route::patch('/mate-requests/{mateRequest}/accept', [CharacterController::class, 'acceptMate'])->name('characters.mate-accept');
    Route::get('/inventory', [ShopController::class, 'inventory'])->name('inventory');
    Route::post('/inventory/{inventory}/use', [ShopController::class, 'useItem'])->name('inventory.use');
    Route::post('/shop/{item}/purchase', [ShopController::class, 'purchase'])->middleware('throttle:20,1')->name('shop.purchase');
    Route::patch('/account', [AccountController::class, 'update'])->middleware('throttle:10,1')->name('account.update');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

Route::get('/characters/{character}', [CharacterController::class, 'show'])->name('characters.show');
Route::get('/{page}', [ContentController::class, 'show'])->whereIn('page', ['rules', 'guide', 'clans', 'map', 'outsiders', 'privacy', 'contact'])->name('content.page');
Route::post('/theme/{theme}', [ThemeController::class, 'update'])->name('theme.update');

Route::middleware(['auth', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/applications', [StaffApplicationController::class, 'index'])->name('applications');
    Route::patch('/applications/{user}/approve', [StaffApplicationController::class, 'approve'])->name('applications.approve');
    Route::get('/litters', [BreedingController::class, 'review'])->name('litters');
    Route::patch('/litters/kits/{kit}', [BreedingController::class, 'updateKit'])->name('litters.kit');
    Route::get('/reports', [ModerationController::class, 'index'])->name('reports');
    Route::patch('/reports/{report}', [ModerationController::class, 'resolve'])->name('reports.resolve');
    Route::middleware('admin')->group(function () {
        Route::get('/characters', [StaffCharacterController::class, 'index'])->name('characters');
        Route::get('/characters/{character}/edit', [StaffCharacterController::class, 'edit'])->name('characters.edit');
        Route::patch('/characters/{character}', [StaffCharacterController::class, 'update'])->name('characters.update');
    });
});

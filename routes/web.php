<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AdminAdoptionController;
use App\Http\Controllers\AdminAdoptionContentController;
use App\Http\Controllers\AdminContentController;
use App\Http\Controllers\AdminGuideController;
use App\Http\Controllers\AdminItemsController;
use App\Http\Controllers\AdminLifecycleController;
use App\Http\Controllers\AdminOwnershipController;
use App\Http\Controllers\AdminPopulationController;
use App\Http\Controllers\AdminRulesController;
use App\Http\Controllers\AdminStaticPageController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminWorldPageController;
use App\Http\Controllers\AdoptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BreedingController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\CricketLedgerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumModerationController;
use App\Http\Controllers\MemorialController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffApplicationController;
use App\Http\Controllers\StaffCharacterController;
use App\Http\Controllers\StaffControlPanelController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\WorldPageController;
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
Route::get('/adoption', [AdoptionController::class, 'index'])->name('adoption.index');
Route::get('/adoption/create', [AdoptionController::class, 'create'])->middleware('auth')->name('adoption.create');
Route::get('/adoption/{listing}', [AdoptionController::class, 'show'])->name('adoption.show');
Route::get('/sales', [AdoptionController::class, 'sales'])->name('sales.index');
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
    Route::patch('/posts/{post}', [ForumController::class, 'updatePost'])->name('post.update');
    Route::post('/posts/{post}/report', [ModerationController::class, 'reportPost'])->middleware('throttle:10,1')->name('post.report');
    Route::patch('/threads/{thread:slug}/lock', [ForumModerationController::class, 'lockThread'])->name('thread.lock');
    Route::patch('/threads/{thread:slug}/sticky', [ForumModerationController::class, 'stickyThread'])->name('thread.sticky');
    Route::patch('/threads/{thread:slug}/move', [ForumModerationController::class, 'moveThread'])->name('thread.move');
    Route::delete('/threads/{thread:slug}', [ForumModerationController::class, 'destroyThread'])->name('thread.destroy');
    Route::delete('/posts/{post}', [ForumModerationController::class, 'destroyPost'])->name('post.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/characters/create', [CharacterController::class, 'create'])->name('characters.create');
    Route::get('/characters/{character}/edit', [CharacterController::class, 'edit'])->name('characters.edit');
    Route::patch('/characters/{character}', [CharacterController::class, 'update'])->name('characters.update');
    Route::delete('/characters/{character}/items/{characterItem}', [CharacterController::class, 'removeItem'])->name('characters.items.destroy');
    Route::get('/sales/create', [AdoptionController::class, 'createSale'])->name('sales.create');
    Route::post('/characters', [CharacterController::class, 'store'])->middleware('throttle:10,1')->name('characters.store');
    Route::post('/breeding', [BreedingController::class, 'store'])->middleware('throttle:10,1')->name('breeding.store');
    Route::post('/pregnancies/{pregnancy}/birth', [BreedingController::class, 'birth'])->middleware('throttle:10,1')->name('breeding.birth');
    Route::post('/characters/{character}/mate-request', [CharacterController::class, 'requestMate'])->middleware('throttle:10,1')->name('characters.mate-request');
    Route::post('/characters/{character}/mentor-request', [CharacterController::class, 'requestMentor'])->middleware('throttle:10,1')->name('characters.mentor-request');
    Route::post('/adoption/{listing}/claim', [AdoptionController::class, 'claim'])->middleware('throttle:10,1')->name('adoption.claim');
    Route::post('/adoption/{listing}/apply', [AdoptionController::class, 'apply'])->middleware('throttle:5,1')->name('adoption.apply');
    Route::patch('/adoption/{listing}/withdraw', [AdoptionController::class, 'withdraw'])->name('adoption.withdraw');
    Route::post('/sales', [AdoptionController::class, 'storeSale'])->name('sales.store');
    Route::patch('/sales/{listing}/withdraw', [AdoptionController::class, 'withdrawSale'])->name('sales.withdraw');
    Route::post('/sales/{listing}/purchase', [AdoptionController::class, 'purchaseSale'])->name('sales.purchase');
    Route::post('/characters/{character}/transfer', [AdoptionController::class, 'requestTransfer'])->name('characters.transfer');
    Route::patch('/character-transfers/{transfer}/accept', [AdoptionController::class, 'acceptTransfer'])->name('characters.transfer.accept');
    Route::post('/adoption', [AdoptionController::class, 'store'])->middleware('throttle:10,1')->name('adoption.store');
    Route::patch('/adoption/applications/{application}', [AdoptionController::class, 'review'])->name('adoption.review');
    Route::patch('/mate-requests/{mateRequest}/accept', [CharacterController::class, 'acceptMate'])->name('characters.mate-accept');
    Route::patch('/character-relationships/{relationship}/accept', [CharacterController::class, 'acceptMentor'])->name('characters.mentor-accept');
    Route::get('/inventory', [ShopController::class, 'inventory'])->name('inventory');
    Route::post('/inventory/{inventory}/use', [ShopController::class, 'useItem'])->name('inventory.use');
    Route::post('/shop/{item}/purchase', [ShopController::class, 'purchase'])->middleware('throttle:20,1')->name('shop.purchase');
    Route::patch('/account', [AccountController::class, 'update'])->middleware('throttle:10,1')->name('account.update');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

Route::get('/characters/{character}', [CharacterController::class, 'show'])->name('characters.show');
Route::get('/{kind}/{slug}', [WorldPageController::class, 'show'])->whereIn('kind', ['clans', 'outsiders'])->name('world.show');
Route::get('/{page}', [ContentController::class, 'show'])->whereIn('page', ['rules', 'guide', 'clans', 'map', 'outsiders', 'privacy', 'contact'])->name('content.page');
Route::post('/theme/{theme}', [ThemeController::class, 'update'])->name('theme.update');

Route::middleware(['auth', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', StaffControlPanelController::class)->name('index');
    Route::get('/applications', [StaffApplicationController::class, 'index'])->name('applications');
    Route::patch('/applications/{user}/approve', [StaffApplicationController::class, 'approve'])->name('applications.approve');
    Route::get('/litters', [BreedingController::class, 'review'])->name('litters');
    Route::patch('/litters', [BreedingController::class, 'bulkUpdateKits'])->name('litters.bulk-update');
    Route::patch('/litters/kits/{kit}', [BreedingController::class, 'updateKit'])->name('litters.kit');
    Route::get('/reports', [ModerationController::class, 'index'])->name('reports');
    Route::patch('/reports', [ModerationController::class, 'bulkResolve'])->name('reports.bulk-resolve');
    Route::patch('/reports/{report}', [ModerationController::class, 'resolve'])->name('reports.resolve');
    Route::middleware('admin')->group(function () {
        Route::get('/characters', [StaffCharacterController::class, 'index'])->name('characters');
        Route::get('/characters/{character}/edit', [StaffCharacterController::class, 'edit'])->name('characters.edit');
        Route::patch('/characters/{character}', [StaffCharacterController::class, 'update'])->name('characters.update');
        Route::post('/characters/{character}/medicine-mentor', [StaffCharacterController::class, 'assignMedicineMentor'])->name('characters.medicine-mentor');
    });
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users', [AdminUserController::class, 'bulkUpdate'])->name('users.bulk-update');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::get('/world', [AdminWorldPageController::class, 'index'])->name('world.index');
    Route::get('/world/create', [AdminWorldPageController::class, 'create'])->name('world.create');
    Route::post('/world', [AdminWorldPageController::class, 'store'])->name('world.store');
    Route::get('/world/{worldPage}/edit', [AdminWorldPageController::class, 'edit'])->name('world.edit');
    Route::patch('/world/{worldPage}', [AdminWorldPageController::class, 'update'])->name('world.update');
    Route::delete('/world/{worldPage}', [AdminWorldPageController::class, 'destroy'])->name('world.destroy');
    Route::post('/world/images', [AdminWorldPageController::class, 'uploadImage'])->name('world.images.store');
    Route::get('/guide', [AdminGuideController::class, 'edit'])->name('guide.edit');
    Route::patch('/guide', [AdminGuideController::class, 'update'])->name('guide.update');
    Route::post('/guide/images', [AdminGuideController::class, 'uploadImage'])->name('guide.images.store');
    Route::get('/rules', [AdminRulesController::class, 'index'])->name('rules.index');
    Route::patch('/rules', [AdminRulesController::class, 'bulkUpdate'])->name('rules.bulk-update');
    Route::post('/rules/categories', [AdminRulesController::class, 'storeCategory'])->name('rules.categories.store');
    Route::patch('/rules/categories/{category}', [AdminRulesController::class, 'updateCategory'])->name('rules.categories.update');
    Route::delete('/rules/categories/{category}', [AdminRulesController::class, 'destroyCategory'])->name('rules.categories.destroy');
    Route::post('/rules/entries', [AdminRulesController::class, 'storeRule'])->name('rules.entries.store');
    Route::patch('/rules/entries/{rule}', [AdminRulesController::class, 'updateRule'])->name('rules.entries.update');
    Route::delete('/rules/entries/{rule}', [AdminRulesController::class, 'destroyRule'])->name('rules.entries.destroy');
    Route::get('/content', [AdminContentController::class, 'categories'])->name('content.index');
    Route::get('/content/categories', [AdminContentController::class, 'categories'])->name('content.categories');
    Route::patch('/content/categories', [AdminContentController::class, 'bulkUpdateCategories'])->name('content.categories.bulk-update');
    Route::get('/content/boards', [AdminContentController::class, 'boards'])->name('content.boards');
    Route::patch('/content/boards', [AdminContentController::class, 'bulkUpdateBoards'])->name('content.boards.bulk-update');
    Route::get('/content/modules', [AdminContentController::class, 'modules'])->name('content.modules');
    Route::patch('/content/modules', [AdminContentController::class, 'bulkUpdateModules'])->name('content.modules.bulk-update');
    Route::get('/pages/{slug}', [AdminStaticPageController::class, 'edit'])->whereIn('slug', ['privacy', 'contact'])->name('pages.edit');
    Route::patch('/pages/{slug}', [AdminStaticPageController::class, 'update'])->whereIn('slug', ['privacy', 'contact'])->name('pages.update');
    Route::post('/pages/{slug}/images', [AdminStaticPageController::class, 'uploadImage'])->whereIn('slug', ['privacy', 'contact'])->name('pages.images.store');
    Route::get('/adoption', [AdminAdoptionController::class, 'index'])->name('adoption.index');
    Route::get('/adoption/content', [AdminAdoptionContentController::class, 'edit'])->name('adoption.content.edit');
    Route::patch('/adoption/content', [AdminAdoptionContentController::class, 'update'])->name('adoption.content.update');
    Route::post('/adoption/content/images', [AdminAdoptionContentController::class, 'uploadImage'])->name('adoption.content.images.store');
    Route::get('/population', [AdminPopulationController::class, 'index'])->name('population.index');
    Route::get('/items', [AdminItemsController::class, 'index'])->name('items.index');
    Route::get('/lifecycle', [AdminLifecycleController::class, 'index'])->name('lifecycle.index');
    Route::get('/ownership', [AdminOwnershipController::class, 'index'])->name('ownership.index');
    Route::post('/content/categories', [AdminContentController::class, 'storeCategory'])->name('content.categories.store');
    Route::patch('/content/categories/{category}', [AdminContentController::class, 'updateCategory'])->name('content.categories.update');
    Route::post('/content/boards', [AdminContentController::class, 'storeBoard'])->name('content.boards.store');
    Route::patch('/content/boards/{board}', [AdminContentController::class, 'updateBoard'])->name('content.boards.update');
    Route::post('/content/modules', [AdminContentController::class, 'storeModule'])->name('content.modules.store');
    Route::patch('/content/modules/{module}', [AdminContentController::class, 'updateModule'])->name('content.modules.update');
});

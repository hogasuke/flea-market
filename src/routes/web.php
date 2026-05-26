<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;

Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/purchase/{item}', [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/purchase/{item}', [PurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/purchase/{item}/success', [PurchaseController::class, 'success'])->name('purchase.success');
Route::get('/purchase/{item}/address', [AddressController::class, 'show'])->name('purchase.address');
    Route::post('/purchase/{item}/address', [AddressController::class, 'update']);
    Route::post('/items/{item}/like', [LikeController::class, 'toggle'])->name('items.like');
    Route::post('/items/{item}/comments', [CommentController::class, 'store'])->name('items.comments.store');
    Route::get('/mypage', [AuthController::class, 'index']);
    Route::get('/mypage/profile', [ProfileController::class, 'show'])->name('mypage.profile');
    Route::post('/mypage/profile', [ProfileController::class, 'update']);
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');
});

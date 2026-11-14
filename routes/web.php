<?php

use App\Http\Controllers\CancellationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConfirmationController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\TradeItemController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('trades.index');
});

Route::get('/trades', [TradeController::class, 'index'])->name('trades.index');
Route::get('/trades/{trade}', [TradeController::class, 'show'])->name('trades.show');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

Route::middleware('auth')->group(function () {
    Route::get('/categories/create', [CategoryController::class, 'create'])->middleware('can:create,'.Category::class)->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('can:create,'.Category::class)->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->middleware('can:update,category')->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('can:update,category')->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('can:delete,category')->name('categories.destroy');

    Route::get('/trades/create', [TradeController::class, 'create'])->name('trades.create');
    Route::post('/trades', [TradeController::class, 'store'])->name('trades.store');
    Route::get('/trades/{trade}/edit', [TradeController::class, 'edit'])->name('trades.edit');
    Route::put('/trades/{trade}', [TradeController::class, 'update'])->name('trades.update');
    Route::put('/trades/{trade}/categories', [TradeController::class, 'updateCategories'])->name('trades.categories.update');
    Route::put('/trades/{trade}/cash', [TradeController::class, 'updateCash'])->name('trades.cash.update');
    Route::post('/trades/{trade}/publish', [TradeController::class, 'publish'])->name('trades.publish');
    Route::delete('/trades/{trade}/hide', [TradeController::class, 'hide'])->name('trades.hide');

    Route::post('/trades/{trade}/items', [TradeItemController::class, 'store'])->name('trades.items.store');
    Route::put('/trade-items/{tradeItem}', [TradeItemController::class, 'update'])->name('trade-items.update');
    Route::delete('/trade-items/{tradeItem}', [TradeItemController::class, 'destroy'])->name('trade-items.destroy');
    Route::post('/trades/{trade}/evidence', [EvidenceController::class, 'store'])->name('trades.evidence.store');
    Route::delete('/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');
    Route::get('/evidence/{evidence}/download', [EvidenceController::class, 'download'])->name('evidence.download');

    Route::post('/trades/{trade}/negotiations', [NegotiationController::class, 'start'])->name('trades.negotiations.start');
    Route::delete('/trades/{trade}/negotiations', [NegotiationController::class, 'cancel'])->name('trades.negotiations.cancel');
    Route::post('/trades/{trade}/accept', [ConfirmationController::class, 'accept'])->name('trades.accept');
    Route::post('/trades/{trade}/confirm', [ConfirmationController::class, 'confirm'])->name('trades.confirm');
    Route::post('/trades/{trade}/cancel', [CancellationController::class, 'store'])->name('trades.cancel');
    Route::post('/trades/{trade}/reviews', [ReviewController::class, 'store'])->name('trades.reviews.store');

    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/{product}/approve', [ProductController::class, 'approve'])->name('products.approve');
});

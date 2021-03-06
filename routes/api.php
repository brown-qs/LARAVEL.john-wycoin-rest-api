<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortfolioController;
use App\Models\Portfolio;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use Illuminate\Support\Facades\Auth;

Route::post('login', [AuthController::class, 'signin']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
Route::post('register', [AuthController::class, 'signup']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('add-portfolio', [PortfolioController::class, 'addPortfolio']);
    Route::get('get-portfolios', [PortfolioController::class, 'getPortfolios']);
    Route::get('load-portfolio-transactions/{id}', [PortfolioController::class, 'loadPortfolioTransactions']);
    Route::get('load-portfolio-coins/{id}', [PortfolioController::class, 'loadPortfolioCoins']);
    Route::post('coinbase-auth-token', [PortfolioController::class, 'coinbaseAuthToken']);
    Route::get('search/coins', [PortfolioController::class, 'searchCoins']);
    Route::get('search/pairs', [PortfolioController::class, 'searchPairs']);
    Route::get('search/networks', [PortfolioController::class, 'searchNetworks']);
    Route::post('create-custom-transaction', [PortfolioController::class, 'createCustomTransaction']);
    Route::post('update-custom-transaction', [PortfolioController::class, 'updateCustomTransaction']);
    Route::post('delete-portfolio', [PortfolioController::class, 'deletePortfolio']);
    Route::post('update-portfolio', [PortfolioController::class, 'updatePortfolio']);
    Route::post('transaction/delete', [PortfolioController::class, 'deleteTransactions']);
});

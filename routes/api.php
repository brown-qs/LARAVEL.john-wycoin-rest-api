<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortfolioController;

use Illuminate\Support\Facades\Http;

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
    Route::post('coinbase-auth-token/{code}', function ($code) {
        $client_id =
            "cbe15651c9f49ef21ad8d08d8343764a7b772e3859cf309a015e8c4bd428e770";
        $client_secret =
            "f61c22fd167213f43fd1d5cccaa3b7a29247c7622f607920a20450e90165b5ad";
        $redirect_uri = "http://localhost:3000/coinbase-oauth-redirect";


        $response = Http::post('https://api.coinbase.com/oauth/token', [
            'grant_type' => "authorization_code",
            'code' => $code,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
        ]);
        return $response->json();
    });
});

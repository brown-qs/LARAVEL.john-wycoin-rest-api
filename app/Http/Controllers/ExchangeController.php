<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserExchange;
use Binance\API;
use Illuminate\Support\Facades\Http;

class ExchangeController extends Controller
{
  public function addUserExchange(Request $request)
  {
    $request->validate([
      'title'     => 'required',
      'exchange'  => 'required',
      'metadata'  => 'required',
    ]);

    $input = $request->only('title', 'exchange');
    $input['metadata'] = json_encode($request->metadata);
    $input['user_id'] = Auth::id();
    $exchange = UserExchange::create($input);

    return $this->sendResponse($exchange, 'Exchange created successfully.');
  }

  public function getUserExchanges()
  {
    return $this->sendResponse(Auth::user()->exchanges, 'Exchanges.');
  }

  public function loadExchangeTransactions($id)
  {
    $exchange = UserExchange::find($id);
    $metadata = json_decode($exchange->metadata, true);
    $api = new API($metadata['api_key'], $metadata['api_secret']);
    // return $this->sendResponse($api->orders("USDTBTC"), 'Exchange created successfully.');
    $res = Http::withHeaders([
      'uuid' => '3697866f-3e7b-4bc7-8f19-8ff7760ecee6',
    ])->post("https://api.coin-stats.com/v4/portfolios/exchange", [
      "name" => "yahoo",
      "additionalInfo" => [
        "apiKey" => "RylCsoapptafZcIUJOLpQ0qKvA0FXg3vRlAAY3tOJTpSCFkcGTmZl6aRkKW243Oi",
        "apiSecret" => "Oxpm9POBRoxIOHaaXZNNG0cJV5iCcOTuVjNCdoBHvpJGPVK4ohMWZ717yElno8HP"
      ],
      "exchangeType" => 3,
      "accounts" => [
        "spot",
        "margin_isolated"
      ],
      "piVersion" => "v6"
    ]);
    $res = $res->json();
    $res = Http::withHeaders([
      'uuid' => '3697866f-3e7b-4bc7-8f19-8ff7760ecee6',
    ])->get('https://api.coin-stats.com/v6/transactions', [
      'portfolioId' => $res[0]['i'],
      'limit' => 100,
    ]);
    return $this->sendResponse($res->json(), 'Exchange created successfully.');
  }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Portfolio;
use App\Models\CustomTransaction;
use Illuminate\Support\Facades\Http;

class PortfolioController extends Controller
{
  public function addPortfolio(Request $request)
  {
    $request->validate([
      'title'     => 'required',
      'exchange'  => 'required',
      'metadata'  => 'required',
    ]);

    $input = $request->only('title', 'exchange');
    $input['metadata'] = json_encode($request->metadata);
    $input['user_id'] = Auth::id();

    $exchangeTypes = ['binance' => 3, 'coinbase' => 10, 'kraken' => 9, 'gate_io' => 36, 'ftx' => 26];

    $connectionIds = ['metamask' => 'metamask', 'ledger' => 'KjOVPzHTpv'];

    if (isset($connectionIds[$request->exchange])) { // Wallet

      $connectionId = $connectionIds[$request->exchange];

      $res = Http::withHeaders([
        'uuid' => '3697866f-3e7b-4bc7-8f19-8ff7760ecee6',
      ])->post("https://api.coin-stats.com/v5/portfolios/multi_wallet", [
        "name" => "deniska",
        "connectionId" => $connectionId,
        "wallets" => [
          [
            'walletAdress' => $request->metadata['wallet_address'],
            'connectionId' => $request->metadata['chain_id'],
          ],
        ],
        "piVersion" => "v6"
      ]);
      $res = $res->json();
      $input['api_id'] = $res['portfolios'][0]['i'];
    } else if (isset($exchangeTypes[$request->exchange])) { // Exchange
      $exchangeType = $exchangeTypes[$request->exchange];
      $additionalInfo = $request->metadata;
      // $additionalInfo = [
      //   "access_token" => "8e02b652e8ae868ba09134e9cda04b004bf7ed6ba1a334415cba2309db5ee62d",
      //   "token_type" => "bearer",
      //   "expires_in" => 7200,
      //   "refresh_token" => "e39021d91f2aa56a6aa10431dd26d95e4161f249a436150ee5c24d7a364f43e5",
      //   "scope" => "wallet:accounts:read wallet:transactions:read wallet:deposits:read wallet:withdrawals:read wallet:addresses:read wallet:addresses:create wallet:user:read wallet:user:email",
      //   "created_at" => 1637856964
      // ];
      $res = Http::withHeaders([
        'uuid' => '3697866f-3e7b-4bc7-8f19-8ff7760ecee6',
      ])->post("https://api.coin-stats.com/v4/portfolios/exchange", [
        "name" => "deniska",
        "additionalInfo" => $additionalInfo,
        "exchangeType" => $exchangeType,
        "piVersion" => "v6",
        'accounts' =>
        [
          "spot",
          "margin_isolated"
        ]
      ]);
      $res = $res->json();
      $input['api_id'] = $res[0]['i'];
    }
    $exchange = Portfolio::create($input);

    return $this->sendResponse($exchange, 'Exchange created successfully.');
  }

  public function getPortfolios()
  {
    $result = Auth::user()->exchanges;
    foreach ($result as &$one) {
      $one['metadata'] = json_decode($one['metadata'], true);
    }
    return $this->sendResponse($result, 'Exchanges.');
  }

  function coinbaseAuthToken(Request $request)
  {
    $client_id =
      "cbe15651c9f49ef21ad8d08d8343764a7b772e3859cf309a015e8c4bd428e770";
    $client_secret =
      "f61c22fd167213f43fd1d5cccaa3b7a29247c7622f607920a20450e90165b5ad";
    $redirect_uri = env('FRONTEND_ORIGIN') . "/coinbase-oauth-redirect";


    $response = Http::post('https://api.coinbase.com/oauth/token', [
      'grant_type' => "authorization_code",
      'code' => $request->code,
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'redirect_uri' => $redirect_uri,
    ]);
    $response =  $response->json();
    return $this->sendResponse($response, 'Exchanges.');
  }

  public function loadPortfolioTransactions($id)
  {
    $exchange = Portfolio::find($id);
    $result = [];
    if ($exchange->exchange === 'custom') {
      $transactions = $exchange->custom_transactions;
      foreach ($transactions as &$one) {
        $one['current_value'] = $one['amount'];
        $one['profit_lose_amount'] = 0;
        $one['direction'] = 1;
      }
      $result = $transactions;
    } else {
      $response = Http::withHeaders([
        'uuid' => '3697866f-3e7b-4bc7-8f19-8ff7760ecee6',
      ])->get('https://api.coin-stats.com/v6/transactions', [
        'portfolioId' => $exchange->api_id,
        'limit' => 100,
      ]);
      $response = $response->json()['transactions'];
      foreach ($response as $i => $one) {
        $result[] = [
          'id' => $i,
          'type' => $one['t'] ?? $one['tt'],
          'date' => $one['ad'],
          'coin' => $one['cs'],
          'coin_label' => $one['cd']['n'],
          'coin_img' => $one['cd']['im'] ?? url('/img/wycoin.png'),
          'quantity' => abs($one['c']),
          'amount' => isset($one['tw']) ? abs($one['tw']['USD']) : 0,
          'current_value' => abs($one['cv']['USD']),
          'fees' => isset($one['feeObj']) ? $one['feeObj']['am'] . ' ' . $one['feeObj']['coin']['s'] : '-',
          'profit_lose_percentage' => $one['pp']['USD'],
          'profit_lose_amount' => $one['pt']['USD'],
          'pair_coin' => $one['pc'] ?? 'USD',
          'purchase_price' => isset($one['tw']) && $one['c'] > 0 ? abs($one['tw']['USD'] / $one['c']) : 0,
          'direction' => $one['c'] >= 0 ? 1 : 0
        ];
      }
    }
    return $this->sendResponse($result, 'Exchanges loaded.');
  }

  public function searchNetworks(Request $request)
  {
    $response = Http::get('https://api.coin-stats.com/v4/portfolios/support/binacesmartchain/coins?searchText=' . $request->search);
    $response = $response->json();
    $result = [];
    foreach ($response as $one) {
      if (isset($one['token'])) continue;
      $result[] = $one;
    }
    return $this->sendResponse($result, 'Blockchains loaded.');
  }

  public function searchCoins(Request $request)
  {
    $response = Http::get('https://api.coin-stats.com/v4/coins?&limit=5&keyword=' . $request->search);
    $response = $response->json();
    return $this->sendResponse($response['coins'], 'Coins loaded.');
  }

  public function createCustomTransaction(Request $request)
  {
    $input = $request->all();
    $transaction = CustomTransaction::create($input);
    return $this->sendResponse($transaction, 'Transaction Created.');
  }
}

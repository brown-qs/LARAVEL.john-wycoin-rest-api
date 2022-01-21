<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Portfolio;
use App\Models\CustomTransaction;
use Illuminate\Support\Facades\Http;

class PortfolioController extends Controller
{
  private $uuid = "5f03fee7-efc9-431c-a85c-c388fc54b8c8";

  public function deletePortfolio(Request $request)
  {
    $request->validate([
      'id' => 'required',
    ]);
    Portfolio::find($request->id)->delete();
    return $this->sendResponse(null, 'Exchange deleted successfully.');
  }
  public function updatePortfolio(Request $request)
  {
    $inputs = $this->buildData($request);
    if ($inputs) {
      Portfolio::find($request->portfolioId)->update($inputs);
      $response = $request->only('title', 'exchange', 'metadata');
      $response['id'] = $request->portfolioId;
      return $this->sendResponse($response, 'Portfolio Updated successfully.');
    } else {
      return $this->sendError('invalid-portfolio', ['error' => ['Invalid Exchange/Wallet.']], null);
    }
  }

  public function buildData(Request $request)
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
        'uuid' => $this->uuid,
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
      if (count($res['portfolios']) == 0) return false;
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
        'uuid' => $this->uuid,
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
      if (!isset($res[0])) return false;
      $input['api_id'] = $res[0]['i'];
    }
    return $input;
  }

  public function addPortfolio(Request $request)
  {
    $inputs = $this->buildData($request);
    if ($inputs) {
      $exchange = Portfolio::create($inputs);
      $response = $request->only('title', 'exchange', 'metadata');
      $response['id'] = $exchange->id;
      return $this->sendResponse($response, 'Exchange created successfully.');
    } else {
      return $this->sendError('invalid-portfolio', ['error' => ['Invalid Exchange/Wallet.']], null);
    }
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
        $one['current_value'] = $this->getPrice($one['coin']) * $one['quantity'];
        $one['profit_lose_amount'] = $this->get24HProfit($one['coin']);
        $one['direction'] = 1;
      }
      $result = $transactions;
    } else {
      $response = Http::withHeaders([
        'uuid' => $this->uuid,
      ])->get('https://api.coin-stats.com/v6/transactions', [
        'portfolioId' => $exchange->api_id,
        'limit' => 100,
      ]);
      $response = $response->json();
      if (isset($response['transactions'])) {
        foreach ($response['transactions'] as $i => $one) {
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
    }
    return $this->sendResponse($result, 'Transactions loaded.');
  }

  public function loadPortfolioCoins($id)
  {
    $exchange = Portfolio::find($id);
    $result = [];
    if ($exchange->exchange === 'custom') {
      $coins = $exchange->custom_coins;
      $total = 0;
      $profit = 0;
      foreach ($coins as &$one) {
        $one['price'] = $this->getPrice($one['coin']);
        $one['profit_lose_amount'] = $this->get24HProfit($one['coin']);
        $profit += $one['profit_lose_amount'];
        $one['total'] = floatval($one['price']) * abs($one['quantity']);
        $total += $one['total'];
      }
      $result = $coins;
      return $this->sendResponse(['coins' => $result, 'total' => $total, 'profit' => $profit], 'Portfolio Infomation loaded.');
    } else {
      $response = Http::withHeaders([
        'uuid' => $this->uuid,
      ])->get('https://api.coin-stats.com/v6/portfolio_items', [
        'portfolioId' => $exchange->api_id,
        'coinExtraData' => true,
      ]);
      $response = $response->json();
      $profit = 0;
      $total = $response['p']['USD'] ?? 0;
      if (isset($response['pi'])) {
        foreach ($response['pi'] as $one) {
          $result[] = [
            'coin' => $one['coin']['s'],
            'coin_label' => $one['coin']['n'],
            'coin_img' => $one['coin']['ic'] ?? url('/img/wycoin.png'),
            'quantity' => abs($one['c']),
            'price' => $one['p']['USD'],
            'total' => floatval($one['p']['USD']) * abs($one['c']),
            'profit_lose_amount' => $one['pt']['h24']['USD'],
          ];
          $profit += $one['pt']['h24']['USD'];
        }
      }
      return $this->sendResponse(['coins' => $result, 'total' => $total, 'profit' => $profit], 'Portfolio Infomation loaded.');
    }
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

  public function getPrice($coin)
  {
    $response = Http::get('https://api.coinstats.app/public/v1/coins/' . $coin . '?currency=USD');
    $response = $response->json();
    if (isset($response['coin'])) {
      return $response['coin']['price'];
    } else {
      return 0;
    }
  }

  public function get24HProfit($coin)
  {
    $response = Http::get('https://api.coinstats.app/public/v1/charts?period=24h&coinId=' . $coin);
    $response = $response->json();
    if (isset($response['chart'])) {
      $before = $response['chart'][0][1];
      $now = end($response['chart'])[1];
      return $now - $before;
    } else {
      return 0;
    }
  }

  public function createCustomTransaction(Request $request)
  {
    $input = $request->all();
    $transaction = CustomTransaction::create($input);

    $transaction['current_value'] = $this->getPrice($input['coin']) * $input['quantity'];
    $transaction['profit_lose_amount'] = $this->get24HProfit($input['coin']);
    $transaction['direction'] = 1;

    return $this->sendResponse($transaction, 'Transaction Created.');
  }

  public function deleteTransactions(Request $request)
  {
    $request->validate([
      'transactions'  => 'required',
    ]);

    foreach ($request->transactions as $tran) {
      CustomTransaction::find($tran)->delete();
    }
    return $this->sendResponse(null, 'Transactions deleted.');
  }
}

<?php
require_once './vendor/autoload.php';

// 1回の実行URL
const BATCH_SIZE = 2;//500;
const END_POINT_PUBLISH = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlbatch';

// Submission APIのAPIキー
$apiKey = '';
// Submission APIのサイトのドメイン
$siteUrl = 'https://example.com';
// 対象のURL
$urlList = ['https://example.com/','https://example.com/1','https://example.com/2'];

// HTTPクライアント
// debug=trueにすると通信ログが確認できる。
$options = ['http_errors' => false, 'debug' => true, 'verify'=>false, ];
$guzzle = new GuzzleHttp\Client($options);

//URLの配列を1回のAPI送信量に分割（最大500）
$chunks = array_chunk($urlList, BATCH_SIZE);

foreach($chunks as $batchNo=>$batchUrls){
    echo "--- batch no {$batchNo} / size = " . count($batchUrls) .PHP_EOL;

    $parameter = [];
    $parameter["siteUrl"] = $siteUrl;
    // URLの配列をそのままパラメータにセットする
    $parameter["urlList"] = $batchUrls;
    
    // Bing API は post で送信
    $bingApi = END_POINT_PUBLISH . '?' . "apikey=$apiKey";
    $response = $guzzle->post($bingApi, ['json'=>$parameter]);

    $code = $response->getStatusCode();
    $body = $response->getBody()->getContents();

    echo "response code = $code " . PHP_EOL;
    echo "response body = $body " . PHP_EOL;
}

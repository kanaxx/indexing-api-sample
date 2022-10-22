<?php
require_once './vendor/autoload.php';

//認証用のファイル(jsonフォルダの下に置いて書き換える)
$credentialFile = './json/your_credential.json';

//indexing APIを投げるURL（自分のURLに書き換える）
$urlList = ['https://example.com/','https://example.com/1','https://example.com/2'];

// HTTPクライアント
// debug=trueにすると通信ログが確認できる。
$options = ['http_errors' => false, 'debug' => false, 'verify'=>false, ];
$guzzle = new GuzzleHttp\Client($options);

//Indexing APIの準備
$client = new Google_Client();
$client->addScope(Google_Service_Indexing::INDEXING);
$client->setHttpClient($guzzle);
$client->setAuthConfig($credentialFile);
$client->authorize();

$service = new Google_Service_Indexing($client);

foreach($urlList as $n=>$url){
    $param = new Google_Service_Indexing_UrlNotification();
    $param->setType('URL_UPDATED');//登録・更新
    $param->setUrl($url);
    try{
        $response = $service->urlNotifications->publish($param);
        
        //responseから情報取るならこの形
        // https://developers.google.com/search/apis/indexing-api/v3/reference/indexing/rest/v3/urlNotifications#UrlNotification
        var_dump($response->getUrlNotificationMetadata()->getLatestUpdate());
        var_dump($response->getUrlNotificationMetadata()->getLatestUpdate()->getUrl());
        var_dump($response->getUrlNotificationMetadata()->getLatestUpdate()->getNotifyTime());
        var_dump($response->getUrlNotificationMetadata()->getLatestUpdate()->getType());
    }catch(Exception $e){
        //正常に終わらなかったら
        // var_dump($e);
        echo $e->getCode() . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
        //Exceptionのエラーメッセージは、JSON文字列になっているので、エラーメッセージだけ抜き出す。
        var_dump(json_decode($e->getMessage(), true)['error']['message']);
        var_dump(json_decode($e->getMessage(), true)['error']['status']);

        //エラーが起きたらループは抜けておく
        break;
    }
}

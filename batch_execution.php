<?php
require_once './vendor/autoload.php';

//https://developers.google.com/search/apis/indexing-api/v3/quota-pricing
//TODO：DefaultRequestsPerMinutePerProject=600 を超えないように実行速度を調整すること
//TODO：DefaultPublishRequestsPerDayPerProject=200 を超えたときのハンドリングをすること

// 1回の実行URL
const BATCH_SIZE = 100;

//認証用のファイル(jsonフォルダの下に置いて書き換える)
$credentialFile = './json/your-credeintial.json';

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
$client->setUseBatch(true);

$service = new Google_Service_Indexing($client);

//URLの配列を1回のAPI送信量に分割（最大100）
$chunks = array_chunk($urlList, BATCH_SIZE);

foreach($chunks as $batchNo=>$batchUrls){

    //batchのインスタンスはURLのセットごとに作る
    //第二引数はboundaryの値。変える必要性はないのでfalseでよい
    // $batch = new Google_Http_Batch($client,false,'https://indexing.googleapis.com'); と同じ
    $batch = new Google_Http_Batch($client, false, $service->rootUrl);

    foreach($batchUrls as $n=>$url){
        $param = new Google_Service_Indexing_UrlNotification();
        $param->setType('URL_UPDATED');//登録・更新
        $param->setUrl($url);

        //setUseBatch(true)にしてあると、ここでリクエストが送られずrequestインスタンスが返ってくるのでbatchにaddする
        $request = $service->urlNotifications->publish($param);

        //第二引数のキーはresultsのキーになる（渡さなくてもOK）
        $batch->add($request, "api-{$batchNo}-{$n}");
    }

    //addしたリクエストを実行。batchをexecuteすると、結果が複数返ってくる
    $results = $batch->execute();

    foreach($results as $key=>$result){
        
        // 正常なら Google\Service\Indexing\UrlNotification
        // エラーなら Google\Service\Exception
        // クラスに違いがあるので注意
        echo "$key : " . get_class($result) .PHP_EOL;

        //batch実行でエラーが起きると、resultの中身がExceptionクラスになるので、エラーを見極めるにはinstanceofで実施 
        if($result instanceof Exception){
            echo "error code | {$result->getCode()}" . PHP_EOL;
            echo "resson | {$result->getErrors()[0]['reason']}" . PHP_EOL;
            echo "message | {$result->getErrors()[0]['message']}" .PHP_EOL;
            if($result->getCode()==429){
                //実行回数上限を超えたときは、ここでとらえる
            }
            break;
        }else{
            //resultsの中のresultから、notificationを取り出す
            $notification = $result->getUrlNotificationMetadata()->getLatestUpdate();
            //大したものはとれないけど、取るならこの形
            var_dump($result->getUrlNotificationMetadata()->getLatestUpdate()->getUrl());
            var_dump($result->getUrlNotificationMetadata()->getLatestUpdate()->getNotifyTime());
            var_dump($result->getUrlNotificationMetadata()->getLatestUpdate()->getType());

            echo "$key | {$notification->getUrl()} | {$notification->getNotifyTime()}" . PHP_EOL;
        }
        
    }
}

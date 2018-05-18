<?php

$url = 'https://api.twitter.com/1.1/search/tweets.json';

// 認証用のキー 空にしています
$consumer_key = '';
$consumer_secret = '';
$access_token = '';
$access_token_secret = '';

// 各設定用の定数
$q = 'JustinBieber filter:images';
$result_type = 'recent';
$include_entities = true;

$method = 'GET';
$oauth_version = '1.0';
$oauth_signature_method = "HMAC-SHA1";

$oauth_signature_key = rawurlencode($consumer_secret) . '&' . rawurlencode($access_token_secret);
$oauth_nonce = microtime();
$oauth_timestamp = time();

$oauth_signature_params =
    'oauth_consumer_key=' . $consumer_key .
    '&oauth_nonce=' . rawurlencode($oauth_nonce) .
    '&oauth_signature_method=' . $oauth_signature_method .
    '&oauth_timestamp=' . $oauth_timestamp .
    '&oauth_token=' . $access_token .
    '&oauth_version=' . $oauth_version .
    '&q=' . rawurlencode($q) .
    '&result_type=' . rawurlencode($result_type);

$oauth_signature_date = rawurlencode($method) . '&' . rawurlencode($url) . '&' . rawurlencode($oauth_signature_params);
$oauth_signature_hash = hash_hmac('sha1', $oauth_signature_date, $oauth_signature_key, true);
$oauth_signature = base64_encode($oauth_signature_hash);

$http_headers = array("Authorization: OAuth " . 
    'oauth_consumer_key=' . rawurlencode($consumer_key) . 
    ',oauth_nonce='.str_replace(" ","+",$oauth_nonce) . 
    ',oauth_signature_method='. rawurlencode($oauth_signature_method) . 
    ',oauth_timestamp=' . rawurlencode($oauth_timestamp) . 
    ',oauth_token=' . rawurlencode($access_token) . 
    ',oauth_version=' . rawurlencode($oauth_version) . 
    ',q=' . rawurlencode($q) .
    ',result_type=' . rawurlencode($result_type) .
    ',oauth_signature='.rawurlencode($oauth_signature));

$url = $url . '?q=' . rawurlencode($q)
            . '&result_type=' . rawurlencode($result_type);

// API実行
$status_res_json = submit_data_by_curl($url, array(), "get", $http_headers);
$res_str = json_decode($status_res_json, true);

$image_url_array = [];

foreach ((array)$res_str['statuses'] as $twit_result){
    if (isset($twit_result['extended_entities'])) {
        foreach ((array)$twit_result['extended_entities']['media'] as $value_media) {
            if (!in_array($value_media['media_url'], $image_url_array) && count($image_url_array) < 10 ) {
                $image_url_array[] =  $value_media['media_url'] . "\n";
            }
        }
    }
}

$num = 1;

foreach ($image_url_array as $image_url) {
    $image_url = mb_convert_encoding($image_url, "SJIS", "UTF-8");
    $filename = basename($image_url);
    $ext = substr($filename, strrpos($filename, '.') + 1);
    $process_url = 'https://pbs.twimg.com/media/' . $filename;
    $data = @file_get_contents($process_url);
    if ($data) {
        @file_put_contents('image_' . $num . '.' . $ext, $data);
        $num++;
    }
}

function submit_data_by_curl($url, $input_data, $method = "post", $http_headers = [])
{
    // 初期化
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);       // URL
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL証明書を検証しない
    curl_setopt($ch, CURLOPT_TIMEOUT , 5 ) ; // タイムアウトの秒数設定

    // HTTPヘッダー
    curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    // レスポンスを文字列で受け取る

    // リクエストを実行
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}
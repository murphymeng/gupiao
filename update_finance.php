<?php
require 'redbean/rb.php';
header("Content-type: text/html; charset=utf-8");
session_start();
date_default_timezone_set("Asia/Shanghai");
// set up database connection
R::setup('mysql:host=localhost;dbname=stock','root','');
R::freeze(true);
function pr($val) {
  echo "<pre>";
  print_r($val);
  echo "</pre>";
}

$start_time = time();

function crawl_page($url, $symbol, $year, $depth = 1)
{
    static $seen = array();
    if (isset($seen[$url]) || $depth === 0) {
        return;
    }

    $seen[$url] = true;

    // Get cURL resource
    $curl = curl_init();

    $headers = array( 
        "Content-type: text/xml;charset=\"GB2312\"", 
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8", 
        "Cache-Control: max-age=0",
    ); 

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
    curl_setopt($curl, CURLOPT_ENCODING, true);
    curl_setopt($curl, CURLOPT_COOKIE, 'bid=71d1a4b344c50ee0079439faa989e414_i5zd7z8s; xq_a_token=f345a4264508751f4e32585cbd3d88013ce7f3fc; xqat=f345a4264508751f4e32585cbd3d88013ce7f3fc; xq_r_token=31077f39f71620fe2def4b775c96a1c08d7334c6; xq_is_login=1; xq_token_expire=Wed%20Mar%2011%202015%2018%3A33%3A30%20GMT%2B0800%20(CST); __utmt=1; _sid=w6cFXj8wTMwrPTIXLUYOpAAve0ynEs; __utma=1.1620670963.1423577375.1424494100.1424494103.30; __utmb=1.52.9.1424498080790; __utmc=1; __utmz=1.1423577375.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); Hm_lvt_1db88642e346389874251b5a1eded6e3=1424314670,1424401979,1424435720,1424494101; Hm_lpvt_1db88642e346389874251b5a1eded6e3=1424498096');
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36");
    $resp = curl_exec($curl);
    curl_close($curl);
    $resp = mb_convert_encoding($resp, "UTF-8", "GB2312");
    preg_match('/净利润<\/a><\/th>        <td width=\"130\">(.*)<\/td>
        <td width=\"130\">(.*)<\/td>
        <td width=\"130\">(.*)<\/td>
        <td width=\"130\">(.*)<\/td>/', $resp, $matches);
    $arr = array();
    $str = '';
    for($i = 4; $i>= 1; $i--) {
        $str = $matches[$i];
        if (stripos($str, '万元')) {
            $str = str_replace('万元', '', $str);
            $str = str_replace(',', '', $str);
            array_push($arr, $str);
        }
    }
    pr($symbol);
    if (count($arr) == 4) {
        $sql = "update gupiao set profit_{$year} = {$arr[3]} where symbol='{$symbol}'";
        pr($sql);
        R::exec($sql);
    }
}

// $stocks = R::getAll("SELECT symbol FROM gupiao WHERE id <603128 AND id >=601000");

// $stocks = R::getAll("SELECT symbol FROM gupiao WHERE symbol LIKE  'SZ0%' AND symbol >  'SZ002703' order by symbol");


//$stocks = R::getAll("SELECT symbol FROM gupiao WHERE symbol like 'SZ000%' order by symbol");
$stocks = R::getAll("SELECT symbol FROM gupiao order by symbol");



foreach($stocks as $k=>$v) {
    $symbol = $v['symbol'];

    $year = 2014;

    $id = substr($symbol, 2);
    $url = "http://stock.finance.qq.com/corp1/mfratio.php?zqdm={$id}&type={$year}";
    //$url = "http://stock.finance.qq.com/corp1/mfratio.php?zqdm=000416&type={$year}";
    crawl_page($url, $symbol, $year);
}


$end_time = time();

echo "time: " . ($end_time - $start_time);
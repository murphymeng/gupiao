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

function crawl_page($url, $symbol, $depth = 1)
{
    static $seen = array();
    if (isset($seen[$url]) || $depth === 0) {
        return;
    }

    $seen[$url] = true;

    // Get cURL resource
    $curl = curl_init();

    $options = array(
       CURLOPT_URL => $url,
       CURLOPT_RETURNTRANSFER => true,     // return web page
       CURLOPT_HEADER         => false,    // don't return headers
       CURLOPT_FOLLOWLOCATION => true,     // follow redirects
       CURLOPT_ENCODING       => "",       // handle all encodings
       CURLOPT_USERAGENT      => "spider", // who am i
       CURLOPT_AUTOREFERER    => true,     // set referer on redirect
       CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
       CURLOPT_TIMEOUT        => 120,      // timeout on response
       CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
       CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
       CURLOPT_COOKIE => 's=1bad11kte3; bid=58e98d6c1c3ddb599b60f64a9b9e9a18_imap82ee; webp=0; xq_a_token=26be5c487e342e3d5e0048d0ea10799a95bad86c; xqat=26be5c487e342e3d5e0048d0ea10799a95bad86c; xq_r_token=287bc1e64e07cae1729d26d1cbbb45c751586e01; xq_is_login=1; u=4363561585; xq_token_expire=Mon%20Jun%2013%202016%2023%3A49%3A34%20GMT%2B0800%20(CST); Hm_lvt_1db88642e346389874251b5a1eded6e3=1463314893,1463672968,1463900629,1463929112; Hm_lpvt_1db88642e346389874251b5a1eded6e3=1463929113; __utmt=1; __utma=1.2057344656.1459090985.1463902626.1463929114.59; __utmb=1.1.10.1463929114; __utmc=1; __utmz=1.1459090985.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)'
   );

    curl_setopt_array($curl, $options );

    // curl_setopt($curl, CURLOPT_URL, $url);
    // curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    // curl_setopt($curl, CURLOPT_HTTPGET, true);
    // //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    // curl_setopt($curl, CURLOPT_COOKIE, 's=1bad11kte3; bid=58e98d6c1c3ddb599b60f64a9b9e9a18_imap82ee; webp=0; xq_a_token=26be5c487e342e3d5e0048d0ea10799a95bad86c; xqat=26be5c487e342e3d5e0048d0ea10799a95bad86c; xq_r_token=287bc1e64e07cae1729d26d1cbbb45c751586e01; xq_is_login=1; u=4363561585; xq_token_expire=Mon%20Jun%2013%202016%2023%3A49%3A34%20GMT%2B0800%20(CST); Hm_lvt_1db88642e346389874251b5a1eded6e3=1463314893,1463672968,1463900629,1463929112; Hm_lpvt_1db88642e346389874251b5a1eded6e3=1463929113; __utmt=1; __utma=1.2057344656.1459090985.1463902626.1463929114.59; __utmb=1.1.10.1463929114; __utmc=1; __utmz=1.1459090985.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)');
    // curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36");
    $resp = curl_exec($curl);
    curl_close($curl);

    $obj = json_decode($resp);


    // echo substr($symbol, 2);
    // if (substr($symbol, 2) > 600332)
    //     pr($obj);

    $arr = ($obj->chartlist);


    if ($arr && is_array($arr) && count($arr) > 0) {
        pr("解析{$symbol}成功");
    } else {
        pr("解析{$symbol}失败");
        pr($url);
        pr($obj);
        //exit();
        return false;
    }

    foreach($arr as $k=>$v) {
        $time = date('Y-m-d', strtotime($v->time));
        //if (strtotime($time) >= strtotime('2010-01-01')) {
        if (strtotime($time) >= strtotime('2010-04-01')) {

                $sql = "select 1 from day where time='{$time}' and symbol='{$symbol}'";
                if (!R::getAll($sql)) {
                    $sql = "insert into day
                        set symbol='{$symbol}',
                            volume={$v->volume},
                            open={$v->open},
                            high=$v->high,
                            close=$v->close,
                            low=$v->low,
                            chg=$v->chg,
                            percent=$v->percent,
                            turnrate=$v->turnrate,
                            ma5=$v->ma5,
                            ma10=$v->ma10,
                            ma20=$v->ma20,
                            ma30=$v->ma30,
                            dif=$v->dif,
                            dea=$v->dea,
                            macd=$v->macd,
                            time='{$time}'";
                    R::exec($sql);
                }
                //pr("insert {$symbol} {$time} success!");
        }
    }

    //exit();
    //crawl_page($href, $depth - 1);
    //echo "URL:",$url,PHP_EOL,"CONTENT:",PHP_EOL,$dom->saveHTML(),PHP_EOL,PHP_EOL;
}

// $stocks = R::getAll("SELECT symbol FROM gupiao WHERE id <603128 AND id >=601000");

// $stocks = R::getAll("SELECT symbol FROM gupiao WHERE symbol LIKE  'SZ0%' AND symbol >  'SZ002703' order by symbol");


//$stocks = R::getAll("SELECT symbol FROM gupiao WHERE symbol like 'SZ000%' order by symbol");
$stocks = R::getAll("SELECT symbol FROM gupiao where symbol like 'SH6%' order by id");
//pr($stocks);
//exit();
foreach($stocks as $k=>$v) {
    $symbol = $v['symbol'];
    //$url = "http://xueqiu.com/stock/forchartk/stocklist.json?symbol={$symbol}&period=1day&type=before&begin=1428227255804&end=1459763255804";
    $url = "http://xueqiu.com/stock/forchartk/stocklist.json?symbol={$symbol}&period=1day&type=before&begin=1270175415804&end=1459763255804";
    //$url = "http://xueqiu.com/stock/forchartk/stocklist.json?symbol={$symbol}&period=1day&type=before&_=1420903110484";
    crawl_page($url, $symbol);

    //exit();
}

//crawl_page("http://xueqiu.com/stock/forchartk/stocklist.json?symbol=SH600000&period=1day&&period=1day&type=before&_=1420903110484", 'SH600000');

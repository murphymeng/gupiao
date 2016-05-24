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

function crawl_page($url, $type, $depth = 1)
{
    static $seen = array();
    if (isset($seen[$url]) || $depth === 0) {
        return;
    }

    $seen[$url] = true;

    // Get cURL resource
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    curl_setopt($curl, CURLOPT_COOKIE, 's=1bad11kte3; bid=58e98d6c1c3ddb599b60f64a9b9e9a18_imap82ee; xq_a_token=186497e2f3d4c6bb41b3840998a3a017a3e57d4a; xqat=186497e2f3d4c6bb41b3840998a3a017a3e57d4a; xq_r_token=e7f164f7fe8cb859128e3a6ab4891cd61217d2d9; xq_is_login=1; u=4363561585; xq_token_expire=Thu%20Apr%2021%202016%2023%3A15%3A09%20GMT%2B0800%20(CST); Hm_lvt_1db88642e346389874251b5a1eded6e3=1458745636,1458914621,1459090380,1459179667; Hm_lpvt_1db88642e346389874251b5a1eded6e3=1459179667; __utmt=1; __utma=1.2057344656.1459090985.1459090985.1459179667.2; __utmb=1.1.10.1459179667; __utmc=1; __utmz=1.1459090985.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)');
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36");

    $resp = curl_exec($curl);

    curl_close($curl);

    $obj = json_decode($resp);
    $arr = ($obj->data);


    foreach($arr as $k=>$v) {
        $id = substr($v[0], 2);
        $row = R::getRow("select * from gupiao where id={$id}");
        if (!$row) {
            R::exec("insert into gupiao
                        set id='{$id}',
                        symbol='{$v[0]}',
                        name='{$v[1]}',
                        stockType='{$type}'");
        } else {
            R::exec("update gupiao
                        set symbol='{$v[0]}',
                            name='{$v[1]}',
                            stockType='{$type}'
                      where id={$id}");
        }
    }
}
 crawl_page("http://xueqiu.com/stock/quote_order.json?page=1&size=9999&order=asc&exchange=CN&stockType=sha&column=symbol%2Cname%2Ccurrent%2Cchg%2Cpercent%2Clast_close%2Copen%2Chigh%2Clow%2Cvolume%2Camount%2Cmarket_capital%2Cpe_ttm%2Chigh52w%2Clow52w%2Chasexist&orderBy=symbol&_=1420946067348", "sha");

 crawl_page("http://xueqiu.com/stock/quote_order.json?page=1&size=9999&order=desc&exchange=CN&stockType=zxb&column=symbol%2Cname%2Ccurrent%2Cchg%2Cpercent%2Clast_close%2Copen%2Chigh%2Clow%2Cvolume%2Camount%2Cmarket_capital%2Cpe_ttm%2Chigh52w%2Clow52w%2Chasexist&orderBy=percent&_=1424403264197", "zxb");

crawl_page("http://xueqiu.com/stock/quote_order.json?page=1&size=9999&order=desc&exchange=CN&stockType=cyb&column=symbol%2Cname%2Ccurrent%2Cchg%2Cpercent%2Clast_close%2Copen%2Chigh%2Clow%2Cvolume%2Camount%2Cmarket_capital%2Cpe_ttm%2Chigh52w%2Clow52w%2Chasexist&orderBy=percent&_=1424403856620", "cyb");

crawl_page("http://xueqiu.com/stock/quote_order.json?page=1&size=9999&order=desc&exchange=CN&stockType=sza&column=symbol%2Cname%2Ccurrent%2Cchg%2Cpercent%2Clast_close%2Copen%2Chigh%2Clow%2Cvolume%2Camount%2Cmarket_capital%2Cpe_ttm%2Chigh52w%2Clow52w%2Chasexist&orderBy=percent&_=1424878964748", "sza");

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

$all = R::getAll("select  day.symbol,
                          day.percent,
                          day.time,
                          gupiao.name
                     from day 
                     left join gupiao on gupiao.symbol=day.symbol");

echo count($all);

$arr = array();
$res = array();
foreach($all as $k=>$v) {
    $symbol = $v['symbol'];
    $name = $v['name'];
    if ($v['percent'] > 9.8) {
        if (!isset($arr[$symbol])) {
            $arr[$symbol] = 1;
        } else {
            $arr[$symbol]++;
        }
    } else {
        if (isset($arr[$symbol])) {
            if ($arr[$symbol] > 1) {
                array_push($res, array('名称'=>$name, '代码'=>$symbol, '涨停结束时间'=>$v['time'], 'count'=>$arr[$symbol]));
            }
            unset($arr[$symbol]);
        }
    }
}


$arr2 = array();
foreach($res as $k=>$v) {
    if (isset($arr2[$v['count']])) {
        $arr2[$v['count']]++;
    } else {
        $arr2[$v['count']] = 1;
    }
    
}
pr($arr2);
pr($res);

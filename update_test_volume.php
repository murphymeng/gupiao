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


$stocks = R::getAll("SELECT * from test order by symbol, time");

$lastStock = null;
$i = 0;
foreach($stocks as $stock) {
    //pr('mf');
    if ($lastStock && $stock['volume'] && $lastStock['volume'] && ($stock['symbol'] == $lastStock['symbol'])) {
        $volumn_rate = $stock['volume'] / $lastStock['volume'];
        $sql = "update day set volume_rate = {$volumn_rate} where symbol=\"{$stock['symbol']}\" and time=\"{$stock['time']}\"";
        R::exec($sql);
    }
    $lastStock = $stock;
    $i++;
    if ($i > 10) {
        //exit();
    }
}

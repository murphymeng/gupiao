<?php
require 'redbean/rb.php';
header("Content-type: text/html; charset=UTF-8");
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

R::exec("delete from gupiao");

$file = fopen("d:/table1.csv","r");
$i = 0;
while(!feof($file))
{
  $line = fgetcsv($file);


  if ($i > 0) {
    $id = $line[0];
    $symbol = $line[1];


    $totalShares =  iconv("GB2312", "UTF-8", $line[30]);
    if (substr($totalShares, -3) === '亿') {
        $totalShares = $totalShares * 100000000;
    } else if (substr($totalShares, -3) === '万') {
        $totalShares = $totalShares * 10000;
    }

    if (substr($symbol, 0, 1) == '0' || substr($symbol, 0, 1) == '3') {
        $symbol = 'SZ' . $symbol;
    } else if (substr($symbol, 0, 1) == '6') {
        $symbol = 'SH' . $symbol;
    }

    if (!$line[2]) {
        continue;
    }
    //pr($line);
    $name =  iconv("GB2312", "UTF-8", $line[2]);
    $row = R::getRow("select * from gupiao where symbol='{$symbol}'");
    if (!$row) {
        $sql = "insert into  gupiao set
                     symbol='{$symbol}',
                       name='{$name}',
                       totalShares='{$totalShares}',
                       id={$id}";

        //pr($sql);
        R::exec($sql);
    } else {
        // $sql = "insert into  gupiao set
        //              symbol='{$symbol}',
        //                name='{$name}'";
        //                pr($sql);
        // R::exec("update gupiao
        //             set symbol='$symbol}',
        //                 name='{$name}',
        //           where id={$symbol}");
    }
  }
  $i++;
}

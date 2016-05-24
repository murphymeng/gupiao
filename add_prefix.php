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

$rows = R::getAll("select * from gupiao where 1");


foreach($rows as $row) {
    if (substr($row['symbol'], 0, 1) == '0') {
        echo $row['symbol'];
    }
}

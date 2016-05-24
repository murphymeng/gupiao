<?php
header("Content-type: text/html; charset=utf-8");
session_start();
date_default_timezone_set("Asia/Shanghai");
// set up database connection

$start = time();

$db = new mysqli("localhost", "root", "", "stock");

function pr($val) {
  echo "<pre>";
  print_r($val);
  echo "</pre>";
}

function set_newhigh($row, $db, $day) {

    $sql = "update day set newhigh{$day}=1 
             where symbol='{$row['symbol']}'
               and time='{$row['time']}'";
    $db->query($sql);
}

// $all = R::getAll("select  id from day");

//$result = $db->query("select * from day where symbol like 'SZ000%' order by symbol ASC, time ASC");
$result = $db->query("select * from day where  time > '2014-08-20' order by symbol ASC, time ASC");

$i = 0;
$current_symbol = '';
$arr = array();
$day = 30;

while($row = mysqli_fetch_assoc($result)) {
    if ($current_symbol == $row['symbol'] && $current_symbol) {
        if (count($arr) == $day) {
            if ($row['close'] > max($arr)) {
                set_newhigh($row, $db, $day);
            }
            array_shift($arr);
        }
    } else if($current_symbol != $row['symbol'] && $current_symbol) {
        unset($arr);
        $arr = array();
    }
    array_push($arr, $row['close']);
    $current_symbol = $row['symbol'];
}


$end = time();
echo "time passed: ". ($end - $start);

?>



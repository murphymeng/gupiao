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

    $sql = "update day set high_day={$day} 
             where symbol='{$row['symbol']}'
               and time='{$row['time']}'";
    $db->query($sql);
}

// $all = R::getAll("select  id from day");

//$result = $db->query("select * from day where symbol like 'SZ000%' order by symbol ASC, time ASC");
$result = $db->query("select * from day where  time > '2012-06-20' order by symbol ASC, time ASC");

$i = 0;
$current_symbol = '';
$arr = array();

$high_val = 0;
$count = 0;

while($row = mysqli_fetch_assoc($result)) {
    if ($current_symbol == $row['symbol'] && $current_symbol) {

        if ($row['close'] > $high_val && $count < 30) {
          $count = 0;
          $high_val = $row['close'];
        } else if ($row['close'] <= $high_val) {
          $count++;
        } else if ($row['close'] > $high_val && $count >= 30) {
          set_newhigh($row, $db, $count);
          $count = 0;
          $high_val = $row['close'];
        }
        
    } else if($current_symbol != $row['symbol'] && $current_symbol) {
        $high_val = $row['close'];
    }
    array_push($arr, $row['close']);
    $current_symbol = $row['symbol'];
}


$end = time();
echo "time passed: ". ($end - $start);

?>



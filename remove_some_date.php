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


// $all = R::getAll("select  id from day");

// $result = mysql_query("select * from day where symbol like 'SZ3%'");
$result = $db->query("SELECT * FROM `day` where time ='2015-03-06' ORDER BY symbol DESC");
$i = 0;

$arr = array();

$symbol = '';
$time = '';

while($row = mysqli_fetch_assoc($result)) {


    if ($row['symbol'] == $symbol) {
        $sql = "delete from day where id={$row['id']}";
        pr($sql);
        $db->query($sql);
    }

    $symbol = $row['symbol'];

}

$end = time();

echo "time passed: ". ($end - $start);

?>







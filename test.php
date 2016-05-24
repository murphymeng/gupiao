<?php
function pr($val) {
  echo "<pre>";
  print_r($val);
  echo "</pre>";
}
$time = '2015-04-30';
//echo strtotime('+1 day', strtotime($time));
$arr = array('mf'=>1);
pr($arr);

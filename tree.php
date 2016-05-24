<?php

function pr($v) {
    echo "<pre>";
    print_r($v);
    echo "</pre>";
}

$arr = array(array('id'=>1, 'name'=>'父菜单1' 'pid'=>0), array('id'=>2, 'name'=>'父菜单2', 'pid'=>0)
    array('id'=>3, 'name'=>'子菜单1', 'pid'=>1));


pr($arr);


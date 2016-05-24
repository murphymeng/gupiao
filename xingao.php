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
                          gupiao.name,
                          day.close
                     from day 
                     left join gupiao on gupiao.symbol=day.symbol");

$arr = array();
$close_arr = array();
$res = array();
$pre_symbol = '';


$add_count = 0;
$reduce_count = 0;
foreach($all as $k=>$v) {

    $symbol = $v['symbol'];
    $name = $v['name'];

    if ($symbol != $pre_symbol) {
        unset($close_arr);
        $close_arr = array();
        $close_arr[] = $v['close'];
        $pre_symbol = $symbol;
        continue;
    }

    if (count($close_arr) > 10) {
        if ($v['close'] < min($close_arr)) {
            $temp_arr = array();
            for ($j = $k; $j < $k + 10; $j++) {
                if ($all[$j]['symbol'] == $symbol) {
                    $temp_arr[] = $all[$j]['close'];
                }
            }

            $temp_val =$temp_arr[count($temp_arr) - 1];
            array_push($res,
                array(
                    '代码'=>$v['symbol'],
                    '股票'=>$v['name'],
                    '开始日期'=>$v['time'],
                    '开始价格'=>$v['close'],
                    '10日内最高'=> max($temp_arr),
                    '最大涨幅'=>round(((max($temp_arr) - $v['close']) / $v['close']) * 100, 1) + '%',
                    '最大跌幅'=>round((($v['close'] - min($temp_arr)) / $v['close']) * 100, 1) + '%',
                    '第10日'=>$temp_val
                )
            );
            if ($temp_val > $v['close']) {
                $add_count++;
            } else if ($temp_val < $v['close']) {
                $reduce_count++;
            }
            unset($temp_arr);
            unset($close_arr);
            $close_arr = array();
        }

        //unset($close_arr);
    } else {
        $close_arr[] = $v['close'];
    }

    $pre_symbol = $symbol;
}
?>

<html>
<head>
    <link href="http://cdn.datatables.net/1.10.4/css/jquery.dataTables.css" media="all" rel="stylesheet" type="text/css" />
    <link href="css/style.css" media="all" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="http://cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#example').dataTable();
        } );
    </script>
</head>
<body>
<div class="main">
    <div>
        add count: <?php echo $add_count;?> <br />
        reduce count: <?php echo $reduce_count;?> <br />
    </div>
    <table id="example" class="display" cellspacing="0" width="100%">
        <thead>
        <tr>
            <th>代码</th>
            <th>股票</th>
            <th>开始日期</th>
            <th>开始价格</th>
            <th>最大涨幅</th>
            <th>最大跌幅</th>
            <th>第10日</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($res as $k=>$v):?>
            <tr>
                <td><?php echo $v['代码'];?></td>
                <td><?php echo $v['股票'];?></td>
                <td><?php echo $v['开始日期'];?></td>
                <td><?php echo $v['开始价格'];?></td>
                <td><?php echo $v['最大涨幅'];?></td>
                <td><?php echo $v['最大跌幅'];?></td>
                <td><?php echo $v['第10日'];?></td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>
</body>
</html>



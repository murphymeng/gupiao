<?php
require 'redbean/rb.php';
require 'vendor/autoload.php';
require 'common.php';
require 'lib/Day.php';
header("Content-type: text/html; charset=utf-8");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) && (
       $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST' ||
       $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'DELETE' ||
       $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'PUT' )) {
             header('Access-Control-Allow-Origin: *');
             header("Access-Control-Allow-Credentials: true");
             header('Access-Control-Allow-Headers: X-Requested-With');
             header('Access-Control-Allow-Headers: Content-Type');
             header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT'); // http://stackoverflow.com/a/7605119/578667
             header('Access-Control-Max-Age: 86400');
             if ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] == 'POST') {
                 header('Content-Type:application/json');
             }
      }
  exit;
}


session_start();
R::setup('mysql:host=localhost;dbname=stock','root','');
R::freeze(true);
// set up database connection


$app = new \Slim\Slim(array(
    'debug' => true
));

$app->db = new mysqli("localhost", "root", "", "stock");
$app->db->set_charset("utf8");
$app->get('/', function () use ($app) {
	  $app->contentType('text/html; fcharset=utf-8');
    $app->render('index.html');
});

$app->get('/trades', function () use ($app) {
    $app->contentType('application/json; charset=utf-8');
    $trades = R::getAll("select trade.*, gupiao.name from trade
                                  join gupiao on trade.symbol=gupiao.symbol");
    echo urldecode(json_encode($trades, JSON_UNESCAPED_UNICODE));
});


$app->post('/trades', function () use ($app) {
    $body = $app->request()->getBody();
    $postData = json_decode($body);

    $symbol = '';
    if (substr($postData->symbol, 0, 1) === '6') {
        $symbol = 'SH' . $postData->symbol;
    } else {
        $symbol = 'SZ' . $postData->symbol;
    }

    $res = R::exec("insert into trade set
                                symbol='{$symbol}',
                                type='{$postData->type}',
                                time='{$postData->time}',
                                price='{$postData->price}',
                                amount='{$postData->amount}'");
    echo urldecode(json_encode([], JSON_UNESCAPED_UNICODE));
});

$app->delete('/trades/:id', function ($id) {
    $res = R::exec("delete from trade where id='{$id}'");
    echo urldecode(json_encode([], JSON_UNESCAPED_UNICODE));
});


$app->get('/list', function () use ($app) {
	$app->contentType('text/html; fcharset=utf-8');
    $app->render('list.html');
});


$app->get('/results', function () use ($app) {
  $db = $app->db;
  $app->contentType('application/json; charset=utf-8');
  $from = $app->request()->params('from');
  $to = $app->request()->params('to');
  $newhigh = $app->request()->params('newhigh');
  $criteria = $app->request()->params('criteria');
  $gupiao_filter = $app->request()->params('gupiao_filter');


  if (!$newhigh) {
    $newhigh = '30';
  }
  if (!$criteria) {
    $criteria = 'newhigh';
  }


  $data = array();

  // 获利次数
  $success_time = 0;
  // 亏损次数
  $fail_time = 0;

  if (!$from) {
    $from = Date('Y-m-d');
  }

  $sql = "select count(*) from day where time = '{$from}'";
  $res = $db->query($sql);
  $count = $res->fetch_row()[0];

  $sql = "select max(time) max_time from day";
  $res = $db->query($sql);
  $max_time = $res->fetch_row()[0];


  if ($count == 0 && strtotime($max_time) >= strtotime($from)) {
      echo '{}';
      return;
  }

  $sql = "select max(time) pre_day from day where time < '{$from}'";
  $res = $db->query($sql);
  $pre_day = $res->fetch_row()[0];

  $sql = "select max(time) pre2_day from day where time < '{$pre_day}'";
  $res = $db->query($sql);
  $pre2_day = $res->fetch_row()[0];

  if (strtotime($max_time) < strtotime($from)) {
      $from = $max_time;
  }

  $filter = "";
  if ($criteria == 'newhigh') {
    $filter1 = " newhigh30 = 1 ";
    // 前一天没有创新高
    $filter = " and symbol in (SELECT symbol FROM `day` WHERE newhigh{$newhigh}=0 and time='$pre2_day') ";

  } else if ($criteria == 'highday') {
    $filter1 = " high_day > 0 ";
  } else if ($criteria == 'turnrate') {
    $filter1 = " turnrate = (select max(turnrate) from day where time='{$pre_day}' and turnrate < 80) ";
  }





  //$filter .= " and (day.close - day.open)/ day.open > 0";
  //$filter .= " and (day.close - day.open)/ day.open > 0.01 and (day.close - day.open)/ day.open < 0.04 ";
  $filter2 = "";

  if ($gupiao_filter) {
    $filter2 = " and gupiao.profit_2014/gupiao.profit_2013 > {$gupiao_filter} ";
  }



    $sql = "select
                 day.symbol,
                 1 test,
                 day.open,
                 day.turnrate,
                 day.close,
                 gupiao.name,
                 day.percent,
                 day1.high_day,
                 format((day.close - day.open)*100/day.open, 2) profit,
                 day.time
            from day
            join (SELECT high_day,symbol FROM `day` WHERE {$filter1} and time='{$pre_day}'". $filter .") day1 on day.symbol=day1.symbol
            left join gupiao on day.symbol = gupiao.symbol
           where day.time = '{$from}' {$filter2}
           order by percent DESC";

    //pr($sql);
	  $res = $db->query($sql);
	  $arr = array();
    $total_close = 0;
    $total_open = 0;
    $total_profit = 0;
    $i = 0;

	  while($row = $res->fetch_assoc()) {
        $i++;
        if ($row['profit'] > 0) {
            $success_time++;
        } else if ($row['profit'] < 0) {
            $fail_time++;
        }

        $total_close += $row['close'];
        $total_open += $row['open'];
        $total_profit+= $row['profit'];

        $sql = "select close from day where symbol=\"{$row['symbol']}\" and time >= '{$from}' order by time limit 2,1";
        //  pr($sql);
        $tp_res = $db->query($sql);
        if ($tp_res) {
          $day3_close = $tp_res->fetch_assoc()['close'];
          $row['day3_profit'] = getProfit($row['open'], $day3_close);
        } else {
          $row['day3_profit'] = '';
        }

        $sql = "select close from day where symbol=\"{$row['symbol']}\" and time >= '{$from}' order by time limit 4,1";
        $tp_res = $db->query($sql);
        if ($tp_res) {
          $row['day5_profit'] = getProfit($row['open'], $tp_res->fetch_assoc()['close']);
        } else {
          $row['day5_profit'] = '';
        }

        $sql = "select close from day where symbol=\"{$row['symbol']}\" and time >= '{$from}' order by time limit 9,1";
        $tp_res = $db->query($sql);
        if ($tp_res) {
          $row['day10_profit'] = getProfit($row['open'], $tp_res->fetch_assoc()['close']);
        } else {
          $row['day10_profit'] = '';
        }

        array_push($arr, $row);
    }


    $data['avg_profit'] = number_format($total_profit / $i, 2) . '%';
	  $data['success_time'] = $success_time;
    $data['fail_time'] = $fail_time;
    $data['success_rate'] = number_format($success_time * 100 / $i, 2) . '%';

    // 将上证指数当日涨跌幅加入结果作参考
    $sql = "select format((day.close - day.open)*100/day.open, 2) szzs_profit
                  from day
                 where day.time='{$from}'
                   and day.symbol='SH000001'";

    $res = $db->query($sql);
    $data['szzs_profit'] = $res->fetch_row()[0] . '%';

	  $data['data'] = $arr;
	  echo urldecode(json_encode($data, JSON_UNESCAPED_UNICODE));
});



$app->get('/all_results', function () use ($app) {
  $db = $app->db;
  $app->contentType('application/json; charset=utf-8');
  $from = $app->request()->params('from');
  $to = $app->request()->params('to');
  $data = array();



  if (!$from) {
    $from = Date('Y-m-d');
  }


  $sql = "select distinct time from day where time >= '$from' and time <= '$to'";
  $time_arr = array();

  $res = $db->query($sql);
  $i = 0;

  $arr = array();

  // 获利次数
  $success_time = 0;
  // 亏损次数
  $fail_time = 0;
  // 成功率
  $success_rate = 0;

  $total_profit = 0;

  $sum_profit = 1;

  while($row = $res->fetch_assoc()) {
      $time = $row['time'];

      if ($i == 0) {
          $sql = "select max(time) pre_day from day where time < '{$from}'";
          $res_tmp = $db->query($sql);
          $pre_day = $res_tmp->fetch_row()[0];
      } else {
          $pre_day = $pre_time;
      }



      $sql = "select max(time) pre2_day from day where time < '{$pre_day}'";
      $res_tmp = $db->query($sql);
      $pre2_day = $res_tmp->fetch_row()[0];


      $pre_time = $time;

      $sql = "select
                   day.symbol,
                   day.open,
                   day.turnrate,
                   day.close,
                   gupiao.name,
                   day.percent,
                   format((day.close - day.open)*100/day.open, 2) profit,
                   day.time
              from day join (select symbol from day where turnrate = (select max(turnrate) from day where time='{$pre_day}' and turnrate < 80) and time='{$pre_day}') day1 on day.symbol = day1.symbol
              left join gupiao on day.symbol = gupiao.symbol
             where day.time = '{$time}'";

      $filter = "";
      $filter = " and symbol in (SELECT symbol FROM `day` WHERE newhigh{$newhigh}=0 and time='$pre2_day') ";

      // $sql = "select
      //                day.symbol,
      //                day.open,
      //                day.close,
      //                day.turnrate,
      //                gupiao.name,
      //                day.percent,
      //                format((day.close - day.open)*100/day.open, 2) profit,
      //                day.time
      //           from day
      //           join (SELECT symbol FROM `day` WHERE newhigh{$newhigh}=1 and time='{$pre_day}'". $filter ." ) day1 on day.symbol=day1.symbol
      //           left join gupiao on day.symbol = gupiao.symbol
      //          where day.time = '{$time}' ORDER BY RAND() limit 1";

      $res1 = $db->query($sql);
      $day_row = $res1->fetch_assoc();

      if (count($day_row) == 0) {
        continue;
      }

      $sql = "select close
          from day
         where time>'{$time}' and symbol=\"{$day_row['symbol']}\" order by time limit 1";
      $res2 = $db->query($sql);
      $day_row['close'] = $res2->fetch_row()[0];
      $day_row['profit'] = number_format(($day_row['close'] - $day_row['open'])*100/$day_row['open'], 2);
      // pr( "open: " . $day_row['open'] . ' close: ' . $day_row['close']);
      // pr($day_row['profit']);
      $sql = "select format((day.close - day.open)*100/day.open, 2) szzs_profit
                  from day
                 where day.time='{$time}'
                   and day.symbol='SH000001'";

      $res_tmp = $db->query($sql);
      $day_row['szzs_profit'] = $res_tmp->fetch_row()[0] . '%';

      array_push($arr, $day_row);
      $total_profit += $day_row['profit'];
      $sum_profit = (1 + $day_row['profit'] * 0.01) * $sum_profit;

      if ($day_row['profit'] > 0) {
          $success_time++;
      } else if ($day_row['profit'] < 0) {
          $fail_time++;
      }

      $i++;
  }

  $data['sum_profit'] = number_format(($sum_profit - 1) * 100, 2) . '%';
  $data['avg_profit'] = number_format($total_profit / $i, 2) . '%';
  $data['success_time'] = $success_time;
  $data['fail_time'] = $fail_time;
  $data['success_rate'] = number_format($success_time * 100 / $i, 2) . '%';

  $data['data'] = $arr;
    echo urldecode(json_encode($data, JSON_UNESCAPED_UNICODE));
});


$app->get('/chart', function() use ($app) {
  $app->render('chart.html');
});

$app->get('/days', function() use ($app) {
  $db = $app->db;
  $app->contentType('application/json; charset=utf-8');
  $symbol = $app->request()->params('symbol');
  $name = $app->request()->params('name');
  $end_time = $app->request()->params('time');


  $sql = "select * from day
                  where symbol = '{$symbol}'
                    and time <= '{$end_time}'order by time DESC limit 300, 1";

  $res = $db->query($sql);
  $from_time = $res->fetch_assoc()['time'];

  $sql = "select * from day
                  where symbol = '{$symbol}'
                    and time > '{$from_time}'
                    and time <= '{$end_time}'order by time";

  $res = $db->query($sql);
  $arr = array();
  while($row = $res->fetch_assoc()) {
    array_push($arr, $row);
  }
  $data['symbol'] = $symbol;
  $data['name'] = $name;
  $data['data'] = $arr;
  echo urldecode(json_encode($data, JSON_UNESCAPED_UNICODE));
});

$app->get('/viewperf', function() use ($app) {
    $app->contentType('text/html; fcharset=utf-8');
    $app->render('perf.html');
});

$app->post('/perf', function() use ($app) {
    $db = $app->db;
    $buy_time = $app->request()->params('time');
    $symbol = $app->request()->params('symbol');
    $buy_price = $app->request()->params('buy_price');

    if (!$buy_price) {
      $buy_price = 0;
    }

    $sql = "insert into perf
                    set perf.buy_time='{$buy_time}',
                        perf.symbol='{$symbol}',
                        perf.buy_price='{$buy_price}'";
    $db->query($sql);
});


$app->get('/perf', function() use ($app) {
  $db = $app->db;
  $dayObj = new Day();
  $dayObj->setDB($db);
  $app->contentType('application/json; charset=utf-8');

  $sql = "select perf.symbol,
                 perf.buy_price,
                 perf.sell_price,
                 perf.buy_time,
                 perf.sell_time,
                 day.open,
                 day.close,
                 gupiao.name
            from perf
            join day on perf.symbol = day.symbol
             and perf.buy_time = day.time
            join gupiao on perf.symbol = gupiao.symbol
           order by perf.insert_time DESC";

  $res = $db->query($sql);
  $arr = array();
  $data = array();

  while($row = $res->fetch_assoc()) {
    $day3 = $dayObj->getDay($row, 3);
    $day5 = $dayObj->getDay($row, 5);
    $day10 = $dayObj->getDay($row, 10);
    $day20 = $dayObj->getDay($row, 20);

    if ($row['sell_price'] > 0) {
      $row['profit'] = getProfit($row['buy_price'], $row['sell_price']);
    } else {
      $row['profit'] = 0;
    }

    $row['day1_profit'] = getProfit($row['buy_price'], $row['close']);
    $row['day3_profit'] = getProfit($row['buy_price'], $day3['close']);
    $row['day5_profit'] = getProfit($row['buy_price'], $day5['close']);
    $row['day10_profit'] = getProfit($row['buy_price'], $day10['close']);
    $row['day20_profit'] = getProfit($row['buy_price'], $day20['close']);
    array_push($arr, $row);
  }
  $data['data'] = $arr;
  echo urldecode(json_encode($data, JSON_UNESCAPED_UNICODE));
});

$app->post('/delete_perf', function() use ($app) {
  $db = $app->db;
  $symbol_str = $app->request()->params('symbol_str');
  $sql = "delete from perf where symbol in ($symbol_str)";
  pr($sql);
  $db->query($sql);
});



$app->run();

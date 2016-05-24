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

function getStockByDay($symbol, $day) {
    $stock = R::getRow("select * from day where time='{$day}' and symbol=\"{$symbol}\"");
    return $stock;
}

function addOneDay($day) {
    return date('Y-m-d', strtotime('+1 day', strtotime($day)));
}

function getStockName($symbol) {
    $sql = "select name from gupiao where symbol='{$symbol}'";
    $row = R::getRow($sql);
    return $row['name'];
}


function keepStock($stock, $day, $stopDay) {
    pr('买入日期：' . $stock['time'] . '买入股票：' . getStockName($stock['symbol']));
    $buyPrice = $stock['open'];
    $i = 0;
    $currentDay = $stock['time'];
    while($i < $day) {
        $currentDay = date('Y-m-d', strtotime('+1 day', strtotime($currentDay)));

        if (strtotime($currentDay) > strtotime($stopDay)) {
            return 0;
        }

        $nextDayStock = getStockByDay($stock['symbol'], $currentDay);
        if ($nextDayStock) {
            $i++;
        } else {
            continue;
        }
        if ( $i == ($day - 1) ) {
            $profit = ($nextDayStock['close'] - $buyPrice) / $buyPrice;
            $showProfit = number_format($profit * 100, 2);
            pr('卖出日期：' . $nextDayStock['time'] .  ' 卖出价格:' . $nextDayStock['close'] . ' 利润:' . $showProfit . '%');
            break;
        }
        continue;
    }



    return array('profit'=>$profit, 'day'=>$currentDay);
}

function getSelectedStocks($day) {

    $stocks = R::getAll("SELECT day.symbol,
                                day.time,
                                day.close,
                                gupiao.name,
                                gupiao.totalShares * day.close / 100000000.00 as '市值',
                                day.volume_rate
                                  FROM day join gupiao on day.symbol = gupiao.symbol
                                 where day.percent > 8
                                   and gupiao.totalShares * day.close < 10000000000
                                   and day.macd > 0.21
                                   and day.close > day.ma10
                                   and day.time = '{$day}'
                                   and ((day.volume_rate > 3 and day.volume_rate < 6) or day.volume_rate > 100)
                                 order by gupiao.totalShares * day.close");

    return $stocks;
}

// 根据可选股票列表获取次日要买入的股票
function getBuyStock($stocks, $mustFirst) {


    $time = $stocks[0]['time'];

    if ($mustFirst) {
        $selectedStock = $stocks[0];
        $symbol = $selectedStock['symbol'];
        $sql = "SELECT * FROM `day` WHERE symbol='{$symbol}' and time > '{$time}' order by time  limit 1";
        $buyStock = R::getRow($sql);

        $open_percent = ($buyStock['open'] / $selectedStock['close'] - 1) * 100;
        if ($open_percent < 9.8) {
            return $buyStock;
        } else {
            return null;
        }
    } else {
        $buyStock = null;
        foreach($stocks as $stock) {
            $symbol = $stock['symbol'];
            $sql = "SELECT * FROM `day` WHERE symbol='{$symbol}' and time > '{$time}' order by time  limit 1";
            $tmpBuyStock = R::getRow($sql);

            $open_percent = ($buyStock['open'] / $stock['close'] - 1) * 100;
            if ($open_percent < 9.8) {
                $buyStock = $tmpBuyStock;
                break;
            } else {
                continue;
            }
        }
        return $buyStock;
    }
}

$totalMoney = 1;
$currentDay = '2015-04-08';
$stopDay = '2016-04-01';
$buyCount = 0;


while(strtotime($stopDay) > strtotime($currentDay)) {

    $dayArr = array("2015-04-09", "2015-04-10", "2015-04-13", "2015-04-14", "2015-04-15", "2015-04-16", "2015-04-17", "2015-04-20", "2015-04-21", "2015-04-22", "2015-04-23", "2015-04-24", "2015-04-27", "2015-04-28", "2015-04-29", "2015-04-30", "2015-05-04", "2015-05-05", "2015-05-11", "2015-05-12", "2015-05-13", "2015-05-14", "2015-05-19", "2015-05-20", "2015-05-21", "2015-05-22", "2015-05-25", "2015-05-26", "2015-05-27", "2015-05-28", "2015-05-29", "2015-06-01", "2015-06-02", "2015-06-03", "2015-06-04", "2015-06-05", "2015-06-08", "2015-06-09", "2015-06-10", "2015-06-11", "2015-06-12", "2015-06-15", "2015-06-16", "2015-06-17", "2015-07-21", "2015-07-22", "2015-07-23", "2015-07-24", "2015-08-10", "2015-08-11", "2015-08-12", "2015-08-13", "2015-08-14", "2015-08-17", "2015-09-21", "2015-09-22", "2015-09-24", "2015-10-08", "2015-10-09", "2015-10-12", "2015-10-13", "2015-10-14", "2015-10-15", "2015-10-16", "2015-10-19", "2015-10-20", "2015-10-21", "2015-10-22", "2015-10-23", "2015-10-26", "2015-10-27", "2015-10-28", "2015-10-29", "2015-10-30", "2015-11-02", "2015-11-04", "2015-11-05", "2015-11-06", "2015-11-09", "2015-11-10", "2015-11-11", "2015-11-12", "2015-11-13", "2015-11-16", "2015-11-17", "2015-11-18", "2015-11-19", "2015-11-20", "2015-11-23", "2015-11-24", "2015-11-25", "2015-11-26", "2015-12-17", "2015-12-18", "2015-12-21", "2015-12-22", "2015-12-23", "2015-12-24", "2015-12-25", "2015-12-29", "2016-02-16", "2016-02-17", "2016-02-18", "2016-02-19", "2016-02-22", "2016-02-23", "2016-02-24", "2016-03-02", "2016-03-03", "2016-03-04", "2016-03-07", "2016-03-08", "2016-03-09", "2016-03-14", "2016-03-15", "2016-03-16", "2016-03-17", "2016-03-18", "2016-03-21", "2016-03-22", "2016-03-23", "2016-03-24", "2016-03-25", "2016-03-28", "2016-03-29", "2016-03-30", "2016-03-31", "2016-04-01", "2016-04-05", "2016-04-06", "2016-04-07", "2016-04-08", "2016-04-11", "2016-04-12", "2016-04-13", "2016-04-14", "2016-04-15", "2016-04-18", "2016-04-19");

    $selectedStocks = getSelectedStocks($currentDay);
    // if (!in_array($currentDay, $dayArr)) {
    //     $selectedStocks = array();
    // }


    if (count($selectedStocks) == 0) {
        $currentDay = addOneDay($currentDay);
        continue;
    }

    $buyStock = getBuyStock($selectedStocks, true);


    if ($buyStock) {
        if (strtotime($buyStock['time']) > strtotime('2016-03-25')) {
            break;
        }
        $buyCount++;
        $arr = keepStock($buyStock, 2, $stopDay);

        if (!$arr) {
            echo "???";
            break;
        }
        $currentDay = $arr['day'];
        $totalMoney = $totalMoney * (1 + $arr['profit']);
    } else {
        $currentDay = addOneDay($currentDay);
        continue;
    }



    // $currentDay = $buyStock['time'];
    // $open_percent = ($buyStock['open'] / $selectedStock['close'] - 1) * 100;
    // if ($open_percent < 9.8) {
    //     $arr = keepStock($buyStock, 3, $stopDay);
    //     if (!$arr) {
    //         pr($buyStock);
    //         echo "???";
    //         break;
    //     }
    //     $currentDay = $arr['day'];
    //     $totalMoney = $totalMoney * (1 + $arr['profit']);
    // } else {
    //     // 买不到就等明天
    //     //$currentDay = addOneDay($currentDay);
    // }

}
pr('买入次数：' . $buyCount);
pr('总收益: ' . $totalMoney);

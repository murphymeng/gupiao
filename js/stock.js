/**
 * Created by Administrator on 2015/2/22.
 */
var tb = null;


$(document).ready(function() {
    $('#datetimepicker1').datetimepicker({
        format: 'YYYY-MM-DD'
    });

    $('#datetimepicker2').datetimepicker({
        format: 'YYYY-MM-DD'
    });

    //$('#date-input1').val('2015-02-25');
    $('#date-input1').val(today);
    $('#date-input2').val(today);

    

    // $('#date-input1').val('2012-01-01');
    // $('#date-input2').val('2013-01-01');

    $('#run').click(function() {
        $.ajax({
          type: "GET",
          url: "./results",
          data: {
              from: $('#date-input1').val(),
              to: $('#date-input2').val(),
              newhigh: $('#newhigh').val(),
              criteria: $('#criteria').val(),
              gupiao_filter: $('#gupiao_filter').val()
          }
        }).done(function(data) {
            var res_html = ('成功次数：' + data.success_time + ' 失败次数：' + data.fail_time);
            res_html += (' 成功率：' + data.success_rate + ' 平均收益：' + data.avg_profit + ' 上证指数涨跌幅：' + data.szzs_profit);
            res_html += (' 总收益：' + data.sum_profit);
            $('#test_result').html(res_html);
            if (!tb) {
               tb = $('#example').dataTable({
                    "data": data.data,
                    paging: false,
                    "columns": [
                        { 
                            "data": "symbol" 
                        },
                        { 
                            "data": 'test',
                            render: function(val, d, obj) {
                                return '<button class="test-performance"' +
                                    'time="' + obj.time + '"' +
                                    'symbol="' + obj.symbol + '"' +
                                    'buy_price="' + obj.open + '" type="button">测</button>';
                            }
                        },
                        {
                            "data": "name",
                            render: function(val, d, obj) {
                                return "<a class='chart' name='"+val+"' id='"+ obj.symbol +"'>" + val + "</a><div class='hide'><div name='"+val+"' class='chart-content' id='chart-"+obj.symbol+"'></div></div>";
                            }
                        },
                        { "data": "time" },
                        { "data": "open" },
                        { "data": "close" },
                        { 
                            "data": 'percent',
                            render: colorRender
                        },
                        {
                            "data": 'profit',
                            render: colorRender
                        },
                        {
                            "data": 'day3_profit',
                            render: colorRender
                        },
                        {'data': 'day5_profit',render: colorRender},
                        {'data': 'day10_profit',render: colorRender}
                    ]
                });
            } else {
                tb.fnClearTable(data.data);
                tb.fnAddData(data.data);
            }


            $('.test-performance').click(function() {
                $.ajax({
                  type: "POST",
                  url: "./perf",
                  data: {
                      symbol: $(this).attr('symbol'),
                      time: $(this).attr('time'),
                      buy_price: $(this).attr('buy_price')
                  }
                })
            });



            var symbol, name, targetEl;

            $('.chart').on('mouseover', function() {
                targetEl = this;
                symbol = this.id;
                name = $(this).attr('name');

                $.ajax({
                    url: "./days",
                    data: {
                        name: name,
                        symbol: symbol,
                        time: $('#date-input1').val()
                    }
                }).done(function(data) {
                    var symbol = data.symbol,
                        name = data.name;
                    var data = data.data;
                    // split the data set into ohlc and volume
                    var ohlc = [],
                        volume = [],
                        dataLength = data.length,
                        // set the allowed units for data grouping
                        groupingUnits = [],

                        i = 0,
                        time;

                    for (i; i < dataLength; i += 1) {
                        time = new Date(data[i].time);
                        time = time.getTime();
                        ohlc.push([
                            time, // the date
                            Number(data[i].open), // open
                            Number(data[i].high), // high
                            Number(data[i].low), // low
                            Number(data[i].close), // close,
                            Number(data[i].high_day) // close,
                        ]);

                        volume.push([
                            time, // the date
                            Number(data[i].volume) // the volume
                        ]);
                    }

                    if (!$('#chart-' + symbol).highcharts()) {

                        $('#chart-' + symbol).highcharts('StockChart', {
                            rangeSelector: {
                                buttons: [{
                                    type: 'day',
                                    count: 1,
                                    text: '1d'
                                }, {
                                    type: 'month',
                                    count: 3,
                                    text: '3m'
                                }, {
                                    type: 'year',
                                    count: 1,
                                    text: '1y'
                                }, {
                                    type: 'all',
                                    text: 'All'
                                }],
                                inputEnabled: false, // it supports only days
                                selected : 1, // all
                                inputDateFormat: '%Y-%m-%d'
                            },
                            colors: ['#080', '#434348', '#90ed7d', '#f7a35c', 
                        '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1'],
                            title: {
                                text: name
                            },
                            tooltip: {
                                borderColor: '#434348',
                                crosshairs: true,
                                animation: false,
                                dateTimeLabelFormats: {
                                    day:"<strong>%Y-%m-%d</strong>",
                                }
                            },
                            yAxis: [{
                                labels: {
                                    align: 'right',
                                    x: -3
                                },
                                title: {
                                    text: 'k线图'
                                },
                                height: '76%',
                                lineWidth: 2
                            }, {
                                labels: {
                                    align: 'right',
                                    x: -3
                                },
                                title: {
                                    text: '成交量'
                                },
                                top: '80%',
                                height: '20%',
                                offset: 0,
                                lineWidth: 2
                            }],

                            chart: {
                                type: 'candlestick',
                                zoomType: 'x'
                            },

                            exporting: {
                                enabled: false
                            },

                            series: [{
                                name: name,
                                data: ohlc,
                                dataGrouping : {
                                    units : [
                                        [
                                            'week', // unit name
                                            [1] // allowed multiples
                                        ]
                                    ]
                                }
                            }, {
                                type: 'column',
                                name: '成交量',
                                data: volume,
                                yAxis: 1,
                                dataGrouping: {
                                    enabled: false
                                    //units: groupingUnits
                                }
                            }]
                        });
                    }
                    // create the chart
                    tooltip.pop(targetEl, '#chart-' + symbol);
                });
            });
        });
    });
});


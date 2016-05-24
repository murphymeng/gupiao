<?php
date_default_timezone_set("Asia/Shanghai");
function pr($val) {
	echo "<pre>";
	print_r($val);
	echo "</pre>";
}

function JSON($array) {

	foreach ($array as $key => $value) {
		if(is_array($value)) {
			$array = JSON($value);
		} else {
			$array[$key] = urlencode($value);
		}
	}
	return $array;
}

function better_json_encode($array) {
	return urldecode(json_encode(JSON($array)));
}

function getProfit($open, $close) {
	if (!$close) {
		return 'null';
	} else {
		return number_format(($close - $open) * 100/$open, 2);
	}
}
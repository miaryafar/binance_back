<?php

$start = microtime(true);
$totaltime = 300;
set_time_limit($totaltime);

$timego = 2;
$symbolinit = "BTCUSDT";

$pricemid =0;
$priceconter = 0;
$pricemax = 0;
$pricemin = 999999999;
$quantity = 0;

function initdata(){
$GLOBAL['pricemid'] = 0;
$GLOBAL['priceconter'] = 0 ;
$GLOBAL['pricemax'] = 0;
$GLOBAL['pricemin'] = 999999999;
$GLOBAL['quantity'] = 0;
}


require 'php-binance-api.php';
require 'vendor/autoload.php';
//echo "------------------------1";

$last = 0;
$lastqs = 0;
$lastqb = 0;
$lastm = 1;
$lastnm = 1;

$makersell = 1;
$takersell = 1;
$makerbuy = 1;
$makerunkow = 1;
$takerbuy = 1;
$takerunkow = 1;


include 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// @see home_directory_config.php
// use config from ~/.confg/jaggedsoft/php-binance-api.json
$api = new Binance\API();


function print1($msg,$trades){
	echo $msg;
	echo "	";
	print_r ($trades);
}

$totalQuantity = 0;
$totalSum = 0;
// Trade Updates via WebSocket
$symbol = "ETHUSDT";
$symbolinit = "ETHUSDT";
$api->trades($symbolinit, function($api, $symbol, $trades){
	$GLOBALS['totalSum'] += $trades['price'] * $trades['quantity'];
	$GLOBALS['totalQuantity'] += $trades['quantity'];
	$result = round($GLOBALS['totalSum']/$GLOBALS['totalQuantity'],2);
	if (abs($result - $GLOBALS['last']) > 0.05){
		echo "\n".$result;
	}
	$GLOBALS['last'] = $result;
	

    
});



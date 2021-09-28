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


require '../php-binance-api.php';
require '../vendor/autoload.php';
//echo "------------------------1";

$last = 0;
$lastratio = 0;

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


include '../config.php';

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

// Trade Updates via WebSocket
$symbol = "ETHUSDT";
$symbolinit = "ETHUSDT";
$lastconsulprice =0;
$api->trades($symbolinit, function($api, $symbol, $trades){
	if (fmod($trades['quantity'],1)==0.123){
		print_r ($trades);
		echo "\n".$GLOBALS['last'];
	}
	if ($trades['quantity']==0.123){
		print_r ($trades);
		echo "\n".$GLOBALS['last'];
	}
	
	if($trades['maker']=='true'){
		$GLOBALS['lastm'] +=  $trades['quantity']; //hajm kharid maker
		
	}else{
		$GLOBALS['lastnm'] +=  $trades['quantity']; // hajm kharid taker
		
	}
	
	$ratio = $GLOBALS['lastnm']/$GLOBALS['lastm'];
	$ratiomsg = $ratio > $GLOBALS['lastratio'] ? "up  " : "down";
	$pricemsg = $GLOBALS['last'] < $trades['price'] ? "up  " : "down";
	
	
	if($trades['quantity'] > 1){
		if($trades['maker']=='true'){
			echo "\n 			".$ratiomsg.round($ratio,3)."	".$pricemsg.round($trades['price'],2)."				sell : ".round($trades['quantity'],1).":".round($GLOBALS['lastm']);
		}else{
			echo "\nbuy".round($trades['quantity']).":".round($GLOBALS['lastnm'])."			".$ratiomsg.round($ratio,3)."	".$pricemsg.round($trades['price'],2);
		}
		$GLOBALS['lastconsulprice'] = $trades['price'];
		
	}
	$GLOBALS['last'] = $trades['price'];
	$GLOBALS['lastratio'] = $ratio;
	
	//print_r ($trades);
		

    
});



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
$api->trades($symbolinit, function($api, $symbol, $trades){
	$makersell = $GLOBALS['makersell'];
	$takersell = $GLOBALS['takersell'];
	$makerbuy = $GLOBALS['makerbuy'];
	$takerbuy = $GLOBALS['takerbuy'];
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
		if($GLOBALS['last'] == $trades['price']){
			$GLOBALS['makerunkow']+= 	$trades['quantity'];
		}elseif ($GLOBALS['last'] > $trades['price']){
			$GLOBALS['makersell']+= 	$trades['quantity'];
			//print1('makersell',$trades);
		}else{
			$GLOBALS['makerbuy'] += 	$trades['quantity'];
			//print1('makerbuy',$trades);
		}
	}else{
		$GLOBALS['lastnm'] +=  $trades['quantity']; // hajm kharid taker
		if($GLOBALS['last'] == $trades['price']){
			$GLOBALS['takerunkow']+= 	$trades['quantity'];
		}elseif ($GLOBALS['last'] > $trades['price']){
			$GLOBALS['takersell'] += 	$trades['quantity'];
			//print1('takersell',$trades);
		}else{
			$GLOBALS['takerbuy'] += 	$trades['quantity'];
			//print1('takerbuy',$trades);
		}
	}
	
	
	if($trades['maker']=='false' and $trades['quantity'] > 0.1){
		if($GLOBALS['last'] == $trades['price']){
			echo "\n		".$trades['quantity']."	m:".round($GLOBALS['makerunkow'])."	t:".round($GLOBALS['takerunkow'])."	MS:".round($makersell)."	mb:".round($makerbuy)."	ts:".round($takersell)."	tb:".round($takerbuy)."	". round($trades['price'],2);
		}elseif ($GLOBALS['last'] > $trades['price']){
			$GLOBALS['lastqs'] += $trades['quantity'];
			echo "\n 										sell : ".$trades['quantity'].":".round($GLOBALS['lastqs']);
		}elseif ($GLOBALS['last'] < $trades['price']){
			$GLOBALS['lastqb'] += $trades['quantity'];
			//echo "\n buy : ".$trades['quantity']." totla : ".$GLOBALS['lastqb']."    	". $trades['price'];
			echo "\nbuy".$trades['quantity'].":".round($GLOBALS['lastqb']);
		}
	}
	$GLOBALS['last'] = $trades['price'];
	
	//print_r ($trades);
		

    
});



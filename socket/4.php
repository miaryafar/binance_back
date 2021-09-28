<?php


require '../php-binance-api.php';
require '../vendor/autoload.php';
//echo "------------------------1";

$last = 0;
$lastratio = 0;


$lastm = 1;
$lastnm = 1;

/*
include '../config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
*/


$api = new Binance\API();

function print1($msg,$trades){
	echo $msg;
	echo "	";
	print_r ($trades);
}

// Trade Updates via WebSocket
$symbol = "ETHUSDT";
$symbolinit = "ETHUSDT";
$api->depthCache([$symbolinit], function($api, $symbol, $depth) {
	echo "{$symbol} depth cache update".PHP_EOL;
	//print_r($depth); // Print all depth data
	$limit = 11; // Show only the closest asks/bids
	$sorted = $api->sortDepth($symbol, $limit);
	$bid = $api->first($sorted['bids']);
	$ask = $api->first($sorted['asks']);
	echo $api->displayDepth($sorted);
	echo "ask: {$ask}".PHP_EOL;
	echo "bid: {$bid}".PHP_EOL;
});

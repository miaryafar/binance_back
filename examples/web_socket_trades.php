<?php

$start = microtime(true);
$totaltime = 30;
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





include '../../config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// @see home_directory_config.php
// use config from ~/.confg/jaggedsoft/php-binance-api.json
$api = new Binance\API();

// Trade Updates via WebSocket
$api->trades($symbolinit, function($api, $symbol, $trades) {
    
		
		$GLOBALS['pricemid'] += $trades['price']*$trades['quantity'];
		$GLOBALS['priceconter'] += 1;
		if($GLOBALS['pricemax'] < $trades['price']){ $GLOBALS['pricemax'] = $trades['price'];}
		if($GLOBALS['pricemin'] > $trades['price']){ $GLOBALS['pricemin'] = $trades['price'];}
		$GLOBALS['quantity'] += $trades['quantity'];
	//if ($GLOBALS['start']+$GLOBALS['timego']*(1-0.2/$GLOBALS['totaltime']) < microtime(true)){
	if ($GLOBALS['start']+$GLOBALS['timego']*(1-0.5/$GLOBALS['totaltime']) < microtime(true)){
    //print_r($trades);
	$sql = "INSERT INTO `".$GLOBALS['symbolinit']."`( `pricemax`, `pricemin`, `quantity`, `numtrade`, `mid`) VALUES ('".$GLOBALS['pricemax']."','".$GLOBALS['pricemin']."','".$GLOBALS['quantity']."','".$GLOBALS['priceconter']."','".($GLOBALS['pricemid']/$GLOBALS['quantity'])."');";
	$GLOBALS['conn']->query($sql);
	$last_id = $GLOBALS['conn']->insert_id;
	$sql= "SELECT MAX(`price`) AS min FROM `order` WHERE `status` = 0 AND `top` = 0 AND `symbol`='".$GLOBALS['symbolinit']."'";
	$result = $GLOBALS['conn']->query($sql);
	if ($result->num_rows > 0) {
		//echo "hii";
		while($row = $result->fetch_assoc()) {
			if ($row['min'] >= $GLOBALS['pricemin']){
				$sql = "UPDATE `order` SET `status`='1',`id_init`='".$last_id."' WHERE `order`.`price` = '".$row['min']."' AND `order`.`top` = 0 AND `order`.`symbol` = '".$GLOBALS['symbolinit']."' AND `order`.`status` = 0;";
				$GLOBALS['conn']->query($sql);
			}
		}
	}
	$sql= "SELECT MIN(`price`) AS max FROM `order` WHERE `status` = 0 AND `top` = 1 AND `symbol`='".$GLOBALS['symbolinit']."'";
	$result = $GLOBALS['conn']->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			if ($row['max'] <= $GLOBALS['pricemax']){
				$sql = "UPDATE `order` SET `status`='1',`id_init`='".$last_id."' WHERE `order`.`price` = '".$row['max']."' AND `order`.`top` = 1 AND `order`.`symbol` = '".$GLOBALS['symbolinit']."' AND `order`.`status` = 0;";
				$GLOBALS['conn']->query($sql);
			}
		}
	}
	$GLOBALS['timego'] += 2;
	initdata();
	}
    
});



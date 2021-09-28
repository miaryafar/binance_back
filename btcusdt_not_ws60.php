<?php
$start = microtime(true);
set_time_limit(60);
$symbolinit="BTCUSDT";
require "php-binance-api.php";
// @see home_directory_config.php
// use config from ~/.confg/jaggedsoft/php-binance-api.json
$api = new Binance\API();
// Get Market Depth


include 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}





for ($i = 0; $i < 30; ++$i) {
    
$price = $api->price('BTCUSDT');
$sql = "INSERT INTO `btcusdt`(`pricemax`,`pricemin`) VALUES ($price,$price)";
if ($conn->query($sql) === TRUE) {
	$last_id = $conn->insert_id;
  //echo $last_id."<br>";
} 
	
	$sql= "SELECT MAX(`price`) AS min FROM `order` WHERE `status` = 0 AND `top` = 0 AND `symbol`='".$GLOBALS['symbolinit']."'";
	$result = $GLOBALS['conn']->query($sql);
	if ($result->num_rows > 0) {
		//echo "hii";
		while($row = $result->fetch_assoc()) {
			if ($row['min'] >= $price){
				$sql = "UPDATE `order` SET `status`='1',`id_init`='".$last_id."' WHERE `order`.`price` = '".$row['min']."' AND `order`.`top` = 0 AND `order`.`symbol` = '".$GLOBALS['symbolinit']."' AND `order`.`status` = 0;";
				$GLOBALS['conn']->query($sql);
			}
		}
	}
	$sql= "SELECT MIN(`price`) AS max FROM `order` WHERE `status` = 0 AND `top` = 1 AND `symbol`='".$GLOBALS['symbolinit']."'";
	$result = $GLOBALS['conn']->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			if ($row['max'] <= $price){
				$sql = "UPDATE `order` SET `status`='1',`id_init`='".$last_id."' WHERE `order`.`price` = '".$row['max']."' AND `order`.`top` = 1 AND `order`.`symbol` = '".$GLOBALS['symbolinit']."' AND `order`.`status` = 0;";
				$GLOBALS['conn']->query($sql);
			}
		}
	}
	
    time_sleep_until($start + ($i+1)*2);
}


$conn->close();

?>

<?php
$start = microtime(true);
set_time_limit(600);
$symbola=["BTCUSDT","ZENBTC","ZENETH","ZENUSDT","BTTUSDT","SOLUSDT"];
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


$last_id_array=[];


for ($i = 0; $i < 300; ++$i) {
	foreach ($symbola as $symbol){
		$price = 0 ;
		$price = $api->price($symbol);
		if($price > 0){
			$sql = "INSERT INTO `".strtolower($symbol)."` (`pricemax`,`pricemin`) VALUES ($price,$price)";
			if ($conn->query($sql) === TRUE) {
				$last_id = $conn->insert_id;
				$last_id_array[$symbol] = $last_id;
				$sql="SELECT `top`,`price` , `id` FROM `orders` WHERE `status` = 0 AND `symbol`='".$symbol."'";
				$result = $conn->query($sql);
				while($order = $result->fetch_assoc()) {
					if($order['top'] == 0){
						if ($order['price'] >= $price){
							$sql = "UPDATE `orders` SET `status`='1',`price1` = '".$price."',`id_init`='".$last_id."' WHERE `orders`.`id` = '".$order['id']."' ;";
							$conn->query($sql);
						}
					}elseif ( $order['top'] == 1){
						if ($order['price'] <= $price){
							$sql = "UPDATE `orders` SET `status`='1',`price1` = '".$price."' ,`id_init`='".$last_id."' WHERE `orders`.`id` = '".$order['id']."' ;";
							$conn->query($sql);
						}
					}elseif ( $order['top'] == -1){
						$sql = "UPDATE `orders` SET `status`='1',`price1` = '".$price."' ,`id_init`='".$last_id."' WHERE `orders`.`id` = '".$order['id']."' ;";
						$conn->query($sql);
					}
				}
			}else{
				$sql = "INSERT INTO `log`(`comment`) VALUES ('".$symbol." table quary sql not valid')";
				$conn->query($sql);
			}
		}else{
			$sql = "INSERT INTO `log`(`comment`) VALUES ('".$symbol." table price valued 0')";
			$conn->query($sql);
		}
	}
	// -----depth
	//$conter1 = fmod($i,count($symbola));
	//$depth = $api->depth($symbola[$conter1]);
	//$sql = "INSERT INTO `depth".$symbola[$conter1]."` (`id_price`,`depth`) VALUES (".$last_id_array[$symbola[$conter1]].",'".json_encode($depth)."')";
	//$conn->query($sql);
    time_sleep_until($start + ($i+1)*2);
}


$conn->close();

?>

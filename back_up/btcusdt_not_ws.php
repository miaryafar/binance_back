<?php
$start = microtime(true);
set_time_limit(600);
$symbola=["BTCUSDT","ATOMUSDT"];
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





for ($i = 0; $i < 300; ++$i) {
	foreach ($symbola as $symbol){
    $price = 0 ;
	$price = $api->price($symbol);
	if($price > 0){
		$sql = "INSERT INTO `".strtolower($symbol)."` (`pricemax`,`pricemin`) VALUES ($price,$price)";
		if ($conn->query($sql) === TRUE) {
			$last_id = $conn->insert_id;
			//echo $last_id."<br>";
	 
			// peida kardam max gheimate sefaresh ha dar zir nemoodar
			$sql= "SELECT `price` , `id` FROM `orders` WHERE `status` = 0 AND `top` = 0 AND `symbol`='".$GLOBALS['symbol']."' ORDER BY `price` DESC LIMIT 1";
			$result1 = $conn->query($sql);
			if ($result1->num_rows > 0) {
				//echo "hii";
				while($row = $result1->fetch_assoc()) {
					if ($row['price'] >= $price){
						$sql = "UPDATE `orders` SET `status`='1',`price1` = '".$price."',`id_init`='".$last_id."' WHERE `orders`.`id` = '".$row['id']."' ;";
						$conn->query($sql);
						//$sql = "UPDATE `orders` SET `status`='0' WHERE `orders`.`after` = '".$row['id']."' AND `status`=-1;";
						//$conn->query($sql);
					}
				}
			}
			// peida kardan min gheimate sefarsh dar balaie nemoodar
			$sql= "SELECT `price` , `id` FROM `orders` WHERE `status` = 0 AND `top` = 1 AND `symbol`='".$GLOBALS['symbol']."' ORDER BY `price` ASC LIMIT 1;";
			$result2 = $GLOBALS['conn']->query($sql);
			if ($result2->num_rows > 0) {
				while($row = $result2->fetch_assoc()) {
					if ($row['price'] <= $price){
						$sql = "UPDATE `orders` SET `status`='1',`price1` = '".$price."' ,`id_init`='".$last_id."' WHERE `orders`.`id` = '".$row['id']."' ;";
						$GLOBALS['conn']->query($sql);
						//$sql = "UPDATE `orders` SET `status`='0' WHERE `orders`.`after` = '".$row['id']."' AND `status`=-1;";
						//$conn->query($sql);
					}
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
    time_sleep_until($start + ($i+1)*2);
}


$conn->close();

?>

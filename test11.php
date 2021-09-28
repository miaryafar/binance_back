<?php
$start = microtime(true);
set_time_limit(700);
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





for ($i = 0; $i < 350; ++$i) {
    
$price = $api->price('BTCUSDT');
$sql = "INSERT INTO `btcusdt`(`pricemax`) VALUES ($price)";
if ($conn->query($sql) === TRUE) {
	$last_id = $conn->insert_id;
  echo $last_id."<br>";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}	
	
	
    time_sleep_until($start + ($i+1)*2);
}


$conn->close();

?>

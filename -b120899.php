<?php
$start = microtime(true);
//$symbolinit = "BTCUSDT";
$totaltime = 600;
set_time_limit($totaltime);
 
require 'php-binance-api.php';
$api = new Binance\API("WqZ7dNTAXpfcAHhuGrLHvGzyEGyxsujnzc9ONpBl7xkPPIeTcd4qcjbv0Cvt7oyo","pbvwTRsbMPJCBfcIy6Aab7jfRhTZM1760Jfe1RGFZjrIyI3hr0PTQXPI1mb84h7N");
$api->useServerTime();


include 'config.php';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


function updateAfter($id,$price){
	$sql = "SELECT * FROM `orders` WHERE `orders`.`after` = '".$id."' AND `status`=-1;";
	$result = $GLOBALS['conn']->query($sql);
	while($rowu = $result->fetch_assoc()) {
		if ($rowu['price'] == 0){
	
			$sql = "UPDATE `orders` SET `status`='0',`id_init`='".$GLOBALS['row3']['id']."', price=pricedelta+".$price." WHERE `orders`.`id` = '".$rowu['id']."';";
			$GLOBALS['conn']->query($sql);
		}else{
			$sql = "UPDATE `orders` SET `status`='0',`id_init`='".$GLOBALS['row3']['id']."' WHERE `orders`.`id` = '".$rowu['id']."';";
			$GLOBALS['conn']->query($sql);
		}
	}
	
	// gafter pak kardan
	$sql = "UPDATE `orders` SET `status`='7' WHERE `after` = (SELECT `after` FROM `orders` WHERE `id` = '".$id."') AND `gafter` = (SELECT `gafter` FROM `orders` WHERE `id` = '".$id."') AND `status` = 0;";
	$GLOBALS['conn']->query($sql);
	


}


for ($i = 0; $i < $totaltime/2; ++$i) {
	$sql = "SELECT * FROM `orders` WHERE `status`=1";
	$result = $conn->query($sql);
	if ($result->num_rows > 1) {
		//agar 2 order hamzan faAl bood Anke dirtar amade cancel mishavad
		$sql = "UPDATE `orders` SET `status`='7' ORDER BY `orders`.`id_init` DESC LIMIT 1";
		$result = $conn->query($sql);
	}elseif ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
		if ($row['price'] != 0){
			$operation = Null;
			if($row['buy'] == 1){
				$sql = "SELECT * FROM `".strtolower($row['symbol'])."` ORDER BY `id` DESC  LIMIT 1;";
				$result3 = $conn->query($sql);
				$row3 = $result3->fetch_assoc();
				if($row['top'] == 1){
					//kharid
					$operation = "buy";					
					
				}elseif($row['top'] == 0){
					$sql = "SELECT MIN(`pricemin`) AS min FROM `".strtolower($row['symbol'])."` WHERE `id` >= ".$row['id_init'].";";
					$resultt = $conn->query($sql);
					foreach ($resultt as $row2){}
					if ($row['delta'] >= 0){ 
						if($row2['min']+$row['delta'] <= $row3['pricemax']){
							// kharid
							$operation = "buy";
						}
					}else{ 
						// agar delta manfi bood alamat an ast ke agar be mizan delta paiin rafte bood foroosh anjam nagirad
						if($row['price'] + 2*$row['delta'] >= $row3['pricemax']){
							$operation = "cancel";
						}elseif($row2['min']-$row['delta'] <= $row3['pricemax']){
							// kharid
							$operation = "buy";		
						}
					}
				}
			}elseif ($row['buy'] == 0){
				$sql = "SELECT * FROM `".strtolower($row['symbol'])."` ORDER BY `id` DESC LIMIT 1;";
				$result3 = $conn->query($sql);
				$row3 = $result3->fetch_assoc();
				
				if($row['top'] == 1){
					
					$sql = "SELECT MAX(`pricemax`) AS max FROM `".strtolower($row['symbol'])."` WHERE `id` >= ".$row['id_init'].";";
					$resultt = $conn->query($sql);
					foreach ($resultt as $row2){}
					if ($row['delta'] >= 0){ 
						if($row2['max']-$row['delta'] >= $row3['pricemin']){
							//foroosh
							$operation = "sell";
						}  
					}else{ 
						// agar delta manfi bood alamat an ast ke agar be mizan delta paiin rafte bood foroosh anjam nagirad
						if($row['price'] - 2*$row['delta'] <= $row3['pricemax']){
							$operation = "cancel";
						}elseif($row2['max']+$row['delta'] >= $row3['pricemin']){
							// kharid
							$operation = "sell";		
						}
					}
				}elseif($row['top'] == 0){
						//foroosh
					$operation = "sell";
				}			
			}	
			 
			// operation
			echo $operation."-";
			if ($operation == "buy"){
				$quantity = 0;
				$account = $api->account();
				$pricenow = $api->price($row['symbol']);
				foreach($account['balances'] as $acrow){
					if ($acrow['asset'] == substr($row['symbol'],3)){
					$quantity = $acrow['free']*$row['percentage']/100/$pricenow*0.99;
					$quantity = round($quantity , 4 ) ;
					break;
					}
				}
				$order = $api->buy($row['symbol'], $quantity, 0, "MARKET");
				if (isset($order['fills']['0']['price'])){
					$sql = "UPDATE `orders` SET `status`='3',`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`fillsprice`='".$order['fills']['0']['price']."',`qty`='".$order['fills']['0']['qty']."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$row['id']."';";
					updateAfter($row['id'],$order['fills']['0']['price']);
				}elseif (isset($order['orderId'])){
					$sql = "UPDATE `orders` SET `status`='2',`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$row['id']."';";
				}elseif (isset($order['code'])){
					$sql = "UPDATE `orders` SET `status`='9' ,`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$row['id']."';";
				}else{
					$sql = "UPDATE `orders` SET `status`='8' ,`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$row['id']."';";
				}
				
				$conn->query($sql);
				
			}elseif ($operation == "sell"){
				$quantity = 0;
				$account = $api->account();
				foreach($account['balances'] as $acrow){
					if ($acrow['asset'] == substr($row['symbol'],0,3)){
					$quantity = $acrow['free']*$row['percentage']/100;
					$quantity = round($quantity , 4 ) ;
					break;
					}
				}
				$order = $api->sell($row['symbol'], $quantity, 0, "MARKET");
				if (isset($order['fills']['0']['price'])){
					$sql = "UPDATE `orders` SET `status`='3',`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`fillsprice`='".$order['fills']['0']['price']."',`qty`='".$order['fills']['0']['qty']."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$row['id']."';";
					updateAfter($row['id'],$order['fills']['0']['price']);
				}elseif (isset($order['orderId'])){
					$sql = "UPDATE `orders` SET `status`='2',`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$row['id']."';";
				}elseif (isset($order['code'])){
					$sql = "UPDATE `orders` SET `status`='9' ,`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$row['id']."';";
				}else{
					$sql = "UPDATE `orders` SET `status`='8' ,`id_fill`='".$row3['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$row['id']."';";
				}
				$conn->query($sql);
			}elseif ($operation == "cancel"){
				
				$sql = "UPDATE `orders` SET `status`='5' ,`id_fill`='".$row3['id']."',`result`='cancell operation' WHERE id = '".$row['id']."';";
				$conn->query($sql);
				updateAfter($row['id'],$row3['pricemin']);
			}  
		
		
		}
		}
	}

    time_sleep_until($start + ($i+1)*2);
}


$conn->close();

?>
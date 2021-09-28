<?php
$total_valu_trade = 0.4;
//$symbolinit = "BTCUSDT";
$totaltime = 600;

$start = microtime(true);
set_time_limit($totaltime);
 
require 'php-binance-api.php';
$api = new Binance\API("WqZ7dNTAXpfcAHhuGrLHvGzyEGyxsujnzc9ONpBl7xkPPIeTcd4qcjbv0Cvt7oyo","pbvwTRsbMPJCBfcIy6Aab7jfRhTZM1760Jfe1RGFZjrIyI3hr0PTQXPI1mb84h7N");
$api->useServerTime();

// Create connection
include 'config.php';
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
// get total btc and book dept
$ticker = $api->prices(); // Make sure you have an updated ticker object for this to work
$balances = $api->balances($ticker);
$depth = $api->depth("SXPUSDT");
$sql = "INSERT INTO `totalbtc`(`totalbtc`, `id_btcusdt`,`depth`) VALUES (".$api->btc_value.",(SELECT MAX(id) FROM btcusdt),'".json_encode($depth)."')";
$conn->query($sql);
//loop every 2 sec
for ($i = 0; $i < $totaltime/2; ++$i) {
	
	// find status 1
	$sql = "SELECT * FROM `orders` WHERE `status`=1";
	$sqlOrder = $conn->query($sql);
	if ($sqlOrder->num_rows > 1) {
		//agar 2 order hamzan faAl bood Anke dirtar amade cancel mishavad
		$sql = "UPDATE `orders` SET `status`='7' ORDER BY `orders`.`id_init` DESC LIMIT 1";
		$conn->query($sql);
	}elseif ($sqlOrder->num_rows == 1) {
		$rowOrder = $sqlOrder->fetch_assoc();
		if ($rowOrder['price'] != 0){
			$sql = "SELECT * FROM `".strtolower($rowOrder['symbol'])."` ORDER BY `id` DESC LIMIT 1;";
			$rowLastprice = $conn->query($sql)->fetch_assoc();
			
			$sql = "SELECT MIN(`pricemin`) AS min,MAX(`pricemax`) AS max FROM `".strtolower($rowOrder['symbol'])."` WHERE `id` >= ".$rowOrder['id_init'].";";
			$rowMixPrice = $conn->query($sql)->fetch_assoc();
			
			if($rowOrder['buy'] == 1){
				//if($rowOrder['top'] == 1){
				//	  operation("buy",$rowOrder,$rowLastprice);					
				//}elseif($rowOrder['top'] == 0){
					if ($rowOrder['delta'] >= 0){ 
						if($rowMixPrice['min']+$rowOrder['delta'] <= $rowLastprice['pricemax']){
							operation("buy",$rowOrder,$rowLastprice);
						}
					}else{ 
						// agar delta manfi bood alamat an ast ke agar be mizan delta bala rafte bood kharid anjam nagirad
						if($rowOrder['price'] + 2*$rowOrder['delta'] >= $rowLastprice['pricemax']){
							operation("cancel",$rowOrder,$rowLastprice);
						}elseif($rowMixPrice['min']-$rowOrder['delta'] <= $rowLastprice['pricemax']){
							operation("buy",$rowOrder,$rowLastprice);		
						}
					}
				//}
			}elseif ($rowOrder['buy'] == 0){
				//if($rowOrder['top'] == 1){
					if ($rowOrder['delta'] >= 0){ 
						if($rowMixPrice['max']-$rowOrder['delta'] >= $rowLastprice['pricemin']){
							operation("sell",$rowOrder,$rowLastprice);
						}  
					}else{ 
						// agar delta manfi bood alamat an ast ke agar be mizan delta paiin rafte bood foroosh anjam nagirad
						if($rowOrder['price'] - 2*$rowOrder['delta'] <= $rowLastprice['pricemax']){
							operation("cancel",$rowOrder,$rowLastprice);
						}elseif($rowMixPrice['max']+$rowOrder['delta'] >= $rowLastprice['pricemin']){
							operation("sell",$rowOrder,$rowLastprice);		
						}
					}
				//}elseif($rowOrder['top'] == 0){
				//	operation("sell",$rowOrder,$rowLastprice);
				//}			
			}	
		}else{
			operation("cancel",$rowOrder,$rowLastprice,"becuse of price 0");
		}
	}

    time_sleep_until($start + ($i+1)*2);
}


$conn->close();



function updateAfter($rowOrder,$price){
	$id = $rowOrder['id'];
	$sql = "SELECT * FROM `orders` WHERE `orders`.`after` = '".$id."' AND `status`=-1;";
	$result = $GLOBALS['conn']->query($sql);
	while($rowOrderu = $result->fetch_assoc()) {
		if ($rowOrderu['price'] != 0 AND $rowOrderu['pricedelta'] != 0){
			if($rowOrderu['top'] == 1){
				if ($rowOrderu['pricedelta']+$price > $rowOrderu['price'] ){
					$position = 1;
				}else{
					$position = 2;
				}
			}elseif($rowOrderu['top'] == 0){
				if ($rowOrderu['pricedelta']+$price < $rowOrderu['price'] ){
					$position = 1;
				}else{
					$position = 2;
				}
			}
		}
		
		if ($rowOrderu['price'] == 0 or $position == 1){	
			$sql = "UPDATE `orders` SET `status`='0',`id_init`='".$GLOBALS['rowLastprice']['id']."', price=pricedelta+".$price." WHERE `orders`.`id` = '".$rowOrderu['id']."';";
			$GLOBALS['conn']->query($sql);
		}elseif($rowOrderu['pricedelta']== 0 or $position == 0){
			$sql = "UPDATE `orders` SET `status`='0',`id_init`='".$GLOBALS['rowLastprice']['id']."' WHERE `orders`.`id` = '".$rowOrderu['id']."';";
			$GLOBALS['conn']->query($sql);
		}
	}
	
	// gafter pak kardan
	$sql = "UPDATE `orders` SET `status`='7' WHERE `after` = '".$rowOrder['after']."' AND `gafter` = '".$rowOrder['gafter']."' AND `status` = 0;";
	$GLOBALS['conn']->query($sql);

}

function calQty($rowOrder,$sellOrBuy){
	$total_valu_trade = $GLOBALS['total_valu_trade'];
	if($rowOrder['percentage'] < 0){
		$percentage = $rowOrder['percentage']*-1;
		$api = $GLOBALS['api'];
		$account = $api->account();
		if($sellOrBuy == 1){
			$pricenow = $api->price($rowOrder['symbol']);
			foreach($account['balances'] as $acrow){
				if ($acrow['asset'] == substr($rowOrder['symbol'],3)){
				$quantity = $acrow['free']*$percentage/100/$pricenow*0.99;
				$quantity = round($quantity , 4 ) ;
				break;
				}
			}
		}else{
			foreach($account['balances'] as $acrow){
				if ($acrow['asset'] == substr($rowOrder['symbol'],0,3)){
				$quantity = $acrow['free']*$percentage/100;
				$quantity = round($quantity , 4 ) ;
				break;
				}
			}
		}
			
	}else{
		$quantity = $total_valu_trade*$rowOrder['percentage']/100;
		$quantity = round($quantity , 4 ) ;
	}
	return $quantity;
}

function operation($operation,$rowOrder,$rowLastprice,$comment=NULL){
	$log = "order id:".$rowOrder['id']." - operation:".$operation." - last price id:".$rowLastprice['id'] ;
	try{
		$api = $GLOBALS['api'];
		$update = False;
		if ($operation == "buy"){
			$quantity = calQty($rowOrder,1);
			if($quantity != 0) $order = $api->buy($rowOrder['symbol'], $quantity, 0, "MARKET");
			$update = True;
		}elseif ($operation == "sell"){
			$quantity = calQty($rowOrder,0);		
			if($quantity != 0) $order = $api->sell($rowOrder['symbol'], $quantity, 0, "MARKET");
			$update = True;
		}elseif ($operation == "cancel"){
			$sql = "UPDATE `orders` SET `status`='5' ,`id_fill`='".$rowLastprice['id']."',`result`='cancell operation' WHERE id = '".$rowOrder['id']."';";
			$GLOBALS['conn']->query($sql);
			updateAfter($rowOrder,$rowLastprice['pricemin']);
		}  
		
		if ($update == True){
			$updatePrice = $rowLastprice['pricemin']
			if($quantity == 0){
				$sql = "UPDATE `orders` SET `status`='6' ,`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$rowOrder['id']."';";
			}elseif (isset($order['fills']['0']['price'])){
				$sql = "UPDATE `orders` SET `status`='3',`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`fillsprice`='".$order['fills']['0']['price']."',`qty`='".$order['fills']['0']['qty']."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$rowOrder['id']."';";
				$updatePrice = $order['fills']['0']['price'];
				$log = $log." - price:".$rowLastprice['pricemin']." - last price id:".$rowLastprice['id']."- order:".$rowOrder['buy'].$rowOrder['top']."order price:".$rowOrder['price']." - price fill".$order['fills']['0']['price'];
			}elseif (isset($order['orderId'])){
				$sql = "UPDATE `orders` SET `status`='12',`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$rowOrder['id']."';";
			}elseif (isset($order['code'])){
				$sql = "UPDATE `orders` SET `status`='9' ,`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$rowOrder['id']."';";
				$updatePrice = $order['fills']['0']['price'];
			}else{
				$sql = "UPDATE `orders` SET `status`='8' ,`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$rowOrder['id']."';";
			}
			$GLOBALS['conn']->query($sql);
			updateAfter($rowOrder,$updatePrice);
		}
	//
		
	}catch(Exception $e) {
    
		$log = "Caught exception: ".$e->getMessage()."--".$log;
	}
	
	$sql2 = "INSERT INTO `log` (`id`, `comment`,`result`, `time`) VALUES (NULL, '".$log."','".$order."', CURRENT_TIMESTAMP);";
	$GLOBALS['conn']->query($sql2);
	
	sendMessage($log);
	
}

function sendMessage($messaggio) {
    $token = "448955180:AAGKg9SJypCLrqvxZtANIZDH_5ujHuc2_HQ";
	$chatID = "88322099";

    $url = "https://api.telegram.org/bot" . $token . "/sendMessage?chat_id=" . $chatID;
    $url = $url . "&text=" . urlencode($messaggio);
    $ch = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
?>
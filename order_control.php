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
$sql = "INSERT INTO `totalbtc`(`totalbtc`, `id_btcusdt`) VALUES (".$api->btc_value.",(SELECT MAX(id) FROM btcusdt))";
$conn->query($sql);


//loop every 2 sec
for ($i = 0; $i < $totaltime/2; ++$i) {

	// find status 1
	$sql = "SELECT * FROM `orders` WHERE `status`=1";
	$sqlOrder = $conn->query($sql);
	while($rowOrder = $sqlOrder->fetch_assoc()){
		$sql = "SELECT * FROM `".strtolower($rowOrder['symbol'])."` ORDER BY `id` DESC LIMIT 1;";
		$rowLastprice = $conn->query($sql)->fetch_assoc();
		if ($rowOrder['price'] != 0){
						
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
			}elseif ($rowOrder['buy'] > 1 AND $rowOrder['top'] > 1){  #dar inja buy va top id haie 2 peak hastand ke khat ravand ra ijad mikonand
				$sql = "SELECT `pricemax`  FROM `".strtolower($rowOrder['symbol'])."` WHERE `id` = ".$rowOrder['buy']." OR `id` = ".$rowOrder['top'].";";
				$resultsql = $conn->query($sql);
				$val1 = $resultsql->fetch_assoc();
				$val2 = $resultsql->fetch_assoc();
				$valSpeed = ($val1['pricemax']-$val2['pricemax'])/($rowOrder['buy']-$rowOrder['top']);
				if($valSpeed < 0){
					if($rowLastprice['pricemin'] > ($val1['pricemax']*$valSpeed*($rowLastprice['id'] - $rowOrder['buy']))){
						operation("buy",$rowOrder,$rowLastprice);
					}
				}
				else{
					if($rowLastprice['pricemin'] < ($val1['pricemax']*$valSpeed*($rowLastprice['id'] - $rowOrder['buy']))){
						operation("sell",$rowOrder,$rowLastprice);
					}
				}
				
				
			}
		}elseif($rowOrder['top'] == -1){ 
			if($rowOrder['delay'] > 0){
				if($rowLastprice['id'] - $rowOrder['delay'] >= $rowOrder['id_init']){
					if($rowOrder['buy']==1){
						operation("buy",$rowOrder,$rowLastprice);
					}elseif($rowOrder['buy']==0){
						operation("sell",$rowOrder,$rowLastprice);
					}
				}
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
	if($rowOrder['after'] != 0 ){
		$sql = "UPDATE `orders` SET `status`='7' WHERE `after` = '".$rowOrder['after']."' AND `gafter` = '".$rowOrder['gafter']."' AND `status` = 0;";
		$GLOBALS['conn']->query($sql);
	}

}

function calQty($rowOrder,$sellOrBuy){
	$api = $GLOBALS['api'];
	$total_valu_trade = $GLOBALS['total_valu_trade'];
	
	if ($rowOrder['price']>1000){
		$rnd = 3;
	}elseif($rowOrder['price']>1){
		$rnd = 2;
	}else{
		$rnd = 0;
	}
	
	if($rowOrder['percentage'] > 100){
		$pricenow = $api->price($rowOrder['symbol']);
		$quantity = $rowOrder['percentage']/$pricenow*0.99;
		$quantity = round($quantity , $rnd );
	}elseif($rowOrder['percentage'] > 0){
		$percentage = $rowOrder['percentage'];
		$api = $GLOBALS['api'];
		$account = $api->account();
		if($sellOrBuy == 1){
			$pricenow = $api->price($rowOrder['symbol']);
			foreach($account['balances'] as $acrow){
				if ($acrow['asset'] == substr($rowOrder['symbol'],3) or $acrow['asset'] == substr($rowOrder['symbol'],4)){
					$quantity = $acrow['free']*$percentage/100/$pricenow*0.99;
					$quantity = round($quantity , $rnd ) ;
					break;
				}
			}
		}else{
			foreach($account['balances'] as $acrow){
				if ($acrow['asset'] == substr($rowOrder['symbol'],0,3) or $acrow['asset'] == substr($rowOrder['symbol'],0,4)){
					$quantity = $acrow['free']*$percentage/100*0.99;
					$quantity = round($quantity , $rnd ) ;
					break;
				}
			}
		}
			
	}else{
		$quantity = $total_valu_trade*$rowOrder['percentage']/-100;
		$quantity = round($quantity , $rnd ) ;
	}
	return $quantity;
}

function calfillprice($a){
	$i=0;
	$qty = 0;
	$sum = 0;
	$comission = 0;
	$comission_symbol = "";
	while(isset($a['fills'][$i])){
		$sum += $a['fills'][$i]['price']*$a['fills'][$i]['qty'];
		$qty += $a['fills'][$i]['qty'];
		
		//inja mitavanad check shavad ke hame asset ga ieki bashad
		//$comission += $a['fills'][$i]['commission'];
		//$comission_symbol = $a['fills'][$i]['commissionAsset'];
		
		$i++;
	}
	return $sum/$qty;
	//echo "<br>"."average price is :". $sum/$qty;
	//echo "<br>"."total qty is : ".$qty;
	//echo "<br>"."comiision is:". $comission.$comission_symbol;
}

function operation($operation,$rowOrder,$rowLastprice,$comment=NULL){
	$log = $rowOrder['id']."-".$rowOrder['percentage']."-".$rowOrder['symbol']."-".$operation."-".$rowOrder['top']."-".$rowOrder['price']."-".$rowLastprice['pricemin'] ;
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
			updateAfter($rowOrder,$rowLastprice['pricemin']);
			$GLOBALS['conn']->query($sql);
		}  
		
		if ($update == True){
			if($quantity == 0){
				$sql = "UPDATE `orders` SET `status`='6' ,`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$rowOrder['id']."';";
				updateAfter($rowOrder,$rowLastprice['pricemin']);
			}elseif (isset($order['fills']['0']['price'])){
				$sql = "UPDATE `orders` SET `status`='3',`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`fillsprice`='".calfillprice($order)."',`qty`='".$order['fills']['0']['qty']."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$rowOrder['id']."';";
				updateAfter($rowOrder,calfillprice($order));
			
				$log = $log." - price:".$rowLastprice['pricemin']." - last price id:".$rowLastprice['id']."- order:".$rowOrder['buy'].$rowOrder['top']."order price:".$rowOrder['price']." - price fill".calfillprice($order);
			}elseif (isset($order['orderId'])){
				$sql = "UPDATE `orders` SET `status`='12',`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`orderId`='".$order['orderId']."',`result`='".serialize($order)."' WHERE id = '".$rowOrder['id']."';";
			}elseif (isset($order['code'])){
				$sql = "UPDATE `orders` SET `status`='9' ,`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$rowOrder['id']."';";
				updateAfter($rowOrder,calfillprice($order));
			}else{
				$sql = "UPDATE `orders` SET `status`='8' ,`comment`='".$comment."',`price2`='".$rowLastprice['pricemin']."',`id_fill`='".$rowLastprice['id']."',`origqty`='".$quantity."',`result`='".str_replace("'",'"',serialize($order))."' WHERE id = '".$rowOrder['id']."';";
			}
			$GLOBALS['conn']->query($sql);
		}
	//
		
	}catch(Exception $e) {
    
		$log = "Caught exception: ".$e->getMessage()."--".$log;
	}
	
	$sql2 = "INSERT INTO `log` (`id`, `comment`,`result`, `time`) VALUES (NULL, '".$log."','".$order."', CURRENT_TIMESTAMP);";
	$GLOBALS['conn']->query($sql2);
	
	sendMessage($log);
	
}

function sendSMS($txt) { 
try {
 
    $client = new SoapClient("http://panel.isms.ir/IsmsWebService.wsdl", 
        array('trace' => true, 'exceptions' => true, 'compression' => SOAP_COMPRESSION_ACCEPT, 
        'connection_timeout' => 120, 'cache_wsdl' => WSDL_CACHE_NONE));

    //var_dump($client->__getFunctions());exit;

    //$result = $client->GetReport(array('username'=>'testuser','password'=>'testpass','sender'=>'','ids' =>array("1494558360","1494558361")));

    $result = $client->SendSms(array('username'=>'milad','password'=>'Salam@123',
            'destination'=>array(
                "09123762171",
                "09121464352",
            ),
            'content'=>array(
                $txt,
                $txt ,
            ),'sender' => '982188050578')
    );


    // $result = $client->GetCredit(array('username'=>'testuser','password'=>'testpass'));

    var_dump($result);exit;


} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
}

function sendMessage($messaggio) {
    sendSMS(substr($messaggio,0,100));
	
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
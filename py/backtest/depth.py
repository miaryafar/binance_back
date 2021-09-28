import mysql.connector
import json
mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")
mycursor = mydb.cursor()




def find_in_orderbook(b,price):
	sum_bids=0 #total quantity of bids
	sum_order_bids = 0 #totla quantity of bids match whit price
	for i in b['bids']:
		sum_bids += float(b['bids'][i])
		if (price <= float(i)) :
			sum_order_bids += float(b['bids'][i])
	sum_asks=0
	sum_order_asks=0
	for i in b['asks']:
		sum_asks += float(b['asks'][i])
		if (price >= float(i) ):
			sum_order_asks += float(b['asks'][i])
	return sum_bids,sum_order_bids,sum_asks,sum_order_asks
	

		


mycursor.execute("SELECT `depth`,`id_btcusdt`,`id` FROM `depth` ")
mysql_depth = mycursor.fetchall()


start=0
long = 300
	
#mycursor.execute("SELECT `pricemax`,`id` FROM `xrpusdt` WHERE `id` > "+str(myresult[0][1])+" AND `id` < "+str(myresult[1][1])+" ORDER BY `xrpusdt`.`id`  ASC")
mycursor.execute("SELECT `pricemax`,`id` FROM `xrpusdt` WHERE `id` = "+str(mysql_depth[start][1])+" ")
mysql_price = mycursor.fetchall()
price_now = mysql_price[0][0]


kind = ""
profit = ""
usd= 1000
xrp = 1000/price_now
total_usd = 0
commition = 0.999
comm = 1 - commition
commition_pay = 0
price_old=0
sell_cont,buy_cont = 0,0
sell_cont_p,buy_cont_p = 1,1


for k in range (0,len(mysql_depth)-2):
	mycursor.execute("SELECT `pricemax`,`id` FROM `xrpusdt` WHERE `id` = "+str(mysql_depth[k+1][1])+" ")
	mysql_price = mycursor.fetchall()
	if mysql_price[0][0] : 
		price_now = mysql_price[0][0]
	
	sum_bids,sum_order_bids,sum_asks,sum_order_asks = find_in_orderbook(json.loads(mysql_depth[k][0]),price_now)
	total = sum_bids+sum_asks
	
	if sum_order_bids > 0 :
		if (price_old - price_now) > price_now*comm*2 :
			kind = "buy"
			#quntity = sum_order_bids/total/10
			#quntity = sum_order_bids/sum_bids/20
			quntity = sum_asks/total/100*buy_cont_p
			#quantity = sum_bids/total/100
			xrp += usd*quantity/price_now*commition
			usd -= usd*quantity
			commition_pay += usd*quantity*(1-commition)
			price_old = price_now
			buy_cont += 1
			buy_cont_p += 1
			sell_cont_p = 1
	
	if sum_order_asks > 0 :
		if (price_now - price_old) > price_now*comm*2 :
			kind = "sell"
			#quantity = sum_order_asks/total/10
			#quantity = sum_order_asks/sum_asks/20
			quantity = sum_bids/total/100*sell_cont_p
			#quntity = sum_asks/total/100
			
			usd += xrp*quantity*price_now*commition
			xrp -= xrp*quantity
			commition_pay += xrp*quantity*price_now*(1-commition)
			price_old = price_now
			sell_cont += 1
			sell_cont_p += 1
			buy_cont_p = 1
	profit = "up" if (usd + xrp*price_now)>total_usd else "down"
	total_usd = round(usd + xrp*price_now)
	total_xrp = round(xrp + usd/price_now)
	if k%5000 == 0:
		print (k,price_now,kind,round(quantity*100,3),round(usd),round(xrp),total_usd,"$",total_xrp,profit,round(commition_pay),profit,sell_cont,buy_cont)
		sell_cont,buy_cont = 0,0


print (price_now,kind,round(quantity*100,3),round(usd),round(xrp),total_usd,total_xrp,profit,sell_cont,buy_cont)


symbol = "btcusdt"
long = "100000"
start = "0"
db_order="backtest"
speednum = 100
profit = 105
lost = 100
#func


def speed(num=speednum):
	return price[i] - price[i-num]
def update():
	mycursor.execute("update `"+db_order+"` SET `status` = 5 WHERE `status`=0")
	mydb.commit()
	print(order,id[i],price[i])
	mycursor.execute("update `"+db_order+"` SET `status` = 3,`price1` = %s ,`price2` = %s,`id_fill` = %s WHERE `id`=%s"%(price[i],price[i+1],id[i],order[0]))
	mydb.commit()
	

	
def delta():
	return price[i] - price[i-1]

def spead_av(num=speednum):
	sum,sumw = 0,0
	fib0,fib1=0,1
	for k in range(i-num,i-1):
		fib = fib0+fib1
		fib0 = fib1
		fib1 = fib
		sum+= (price[i]-price[k])*fib
		sumw += fib
	return sum/sumw
	
def neworder(buy):
	update()
	if(buy==1):
		mycursor.execute("INSERT INTO `"+db_order+"` (`buy`,`top`,`price`,`id_init`) VALUES (1,1,%s,%s)"%(price[i+1]+lost,id[i]))
		mydb.commit()
		mycursor.execute("INSERT INTO `"+db_order+"` (`buy`,`top`,`price`,`id_init`) VALUES (1,0,%s,%s)"%(price[i+1]-profit,id[i]))
		mydb.commit()
	else:
		mycursor.execute("INSERT INTO `"+db_order+"` (`buy`,`top`,`price`,`id_init`) VALUES (0,1,%s,%s)"%(price[i+1]+profit,id[i]))
		mydb.commit()
		mycursor.execute("INSERT INTO `"+db_order+"` (`buy`,`top`,`price`,`id_init`) VALUES (0,0,%s,%s)"%(price[i+1]-lost,id[i]))
		mydb.commit()

#connect to my sql
import mysql.connector
mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")
mycursor = mydb.cursor()
#clean database and make a first order
mycursor.execute("DELETE FROM `"+db_order+"` WHERE 1" )
mydb.commit()
mycursor.execute("INSERT INTO `"+db_order+"` (`buy`,`top`,`price`) VALUES (1,1,%s)"%(0))
mydb.commit()
mycursor.execute("INSERT INTO `"+db_order+"` (`buy`,`top`,`price`) VALUES (1,1,%s)"%(0))
mydb.commit()
#get all data

mycursor.execute("SELECT `id`,`pricemax` FROM `"+symbol+"` ORDER BY `id` ASC LIMIT 1000000")
#mycursor.execute("SELECT * FROM (SELECT `id`,`pricemax` FROM `"+symbol+"` ORDER BY `id` DESC LIMIT "+long+" OFFSET "+start+") AS AAA ORDER BY `id` ASC")
myresult = mycursor.fetchall()
id=[el[0] for el in myresult]
price=[el[1] for el in myresult]
del myresult
#order control
#baiad dar ha lahze 2 order bashad ieki top 0 va digari 1
wait = 0
for i in range(speednum,len(id)-1):
	mycursor.execute("SELECT `id`, `buy`,`price` FROM `"+db_order+"` WHERE `status` = 0 ORDER BY `top` ASC")
	myresult = mycursor.fetchall()
	order = myresult[0]
	if price[i] < order[2]: #top 0
		if order[1] == 0: #sell 
			neworder(1)
		else : #buy
			wait += 1
			if(spead_av()>10):
				neworder(0)
				print ("buy",wait,price[i]-price[i-wait])
				wait=0
	order = myresult[1]	
	if price[i] > order[2]:	#top 1
		if order[1] == 1: #buy 
			neworder(0)
		else : #sell
			wait += 1
			if(spead_av()<-10):
				neworder(1)
				print ("sell",wait,price[i]-price[i-wait])
				wait=0
				
				
mycursor.execute("SELECT `id`, `buy`,`price2` FROM `"+db_order+"` WHERE `status` = 3 ORDER BY `id` ASC")
myresult = mycursor.fetchall()
totalprofit = 0
totalprofitc = 0
nump = 0
numl = 0
for row in myresult:
	if (row[1] == 1):
		buy = row[2]
	if (row[1]== 0):
		sell = row[2]
		totalprofit += sell-buy
		totalprofitc += sell*.998-buy*1.002
		printval =""
		if sell-buy > 0 :
			nump += 1
			for h in range(int((sell-buy)//10)): printval += "+"
		else:
			for h in range(int((buy-sell)//10)): printval += "-"
			numl += 1
		print("%s -->  %s"%(printval,(buy-sell)//10))
print (totalprofit)
print (totalprofitc)
print (nump)
print (numl)
print(price[0])
print(price[-1])


	
	
	

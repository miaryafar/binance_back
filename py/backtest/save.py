import mysql.connector
import json
symbol = "zenbtc"
mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")
mycursor = mydb.cursor()
import numpy as np
for i in range(5):
	ofset = i*4000000
	print(ofset)
	mycursor.execute(f"SELECT `id`,`pricemax`,`time` FROM `{symbol}` LIMIT 4000000 OFFSET {ofset}")
	mysql_all = mycursor.fetchall()
	np.save(symbol+str(ofset),mysql_all)






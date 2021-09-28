db_order="backtest"
#connect to my sql
import mysql.connector
mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")
mycursor = mydb.cursor()
#mycursor.execute("SHOW columns FROM backtest")
mycursor.execute("SELECT `id`, `buy`,`price` FROM `"+db_order+"` WHERE `status` = 0 ORDER BY `top` ASC")
myresult = mycursor.fetchall()
print (myresult[0]['id'])

import MySQLdb
db = MySQLdb.connect(host="localhost", user="admin_binance", passwd="yUzT8iH42O",db="admin_binance")
cursor = db.cursor ()   
cursor.execute("SELECT `id`, `buy`,`price` FROM `"+db_order+"` WHERE `status` = 0 ORDER BY `top` ASC")
print(mycursor.attribute)
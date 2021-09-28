def updatePeak(data,distance,width,prominence,status):
	peakind = signal.find_peaks(data,distance=distance,width=width,prominence=prominence)
	for i in range(len(peakind[0])):
		mycursor.execute("UPDATE `btcusdt` SET `peak`='"+str(status)+"'  WHERE `id`="+str(id[peakind[0][i]]))
		mydb.commit()


symbol = "btcusdt"
long = "10000"
start = "0"

import mysql.connector

mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")

mycursor = mydb.cursor()

mycursor.execute("UPDATE `btcusdt` SET `peak`=0 WHERE `peak` != 0")
mydb.commit()


mycursor.execute("SELECT * FROM (SELECT `id`,`pricemax` FROM `"+symbol+"` ORDER BY `id` DESC LIMIT "+long+" OFFSET "+start+") AS AAA ORDER BY `id` ASC")
myresult = mycursor.fetchall()



id=[el[0] for el in myresult]
price=[el[1] for el in myresult]
del myresult

import numpy as np


data = np.array(price)
time = id

from scipy import signal
print ("hi")

#peakind = signal.find_peaks(-1*data,distance=int(100),width=500,prominence=300)
#updatePeak(data=data,distance=100,width=500,prominence=300,status=1)
#updatePeak(data=data,distance=100,width=50,prominence=30,status=1)
updatePeak(data=data,distance=1,width=1,prominence=100,status=1)
updatePeak(data=-1*data,distance=1,width=1,prominence=100,status=-1)




	
	


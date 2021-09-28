#symbol = input("Enter The Symbol") or "btcusdt"
#start = input("Enter start point:")
#long = input ("Enter long of back test:")

symbol = "btcusdt"
long = "30000"
start = "0"

db_order="backtest"

#connect to my sql
import mysql.connector
mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")
mycursor = mydb.cursor()
#clean database and make a first order
mycursor.execute("DELETE FROM `"+db_order+"` WHERE 1" )
mydb.commit()
#clean peaks
mycursor.execute("UPDATE `btcusdt` SET `peak`=0 WHERE `peak` != 0")
mydb.commit()
#get all data

#mycursor.execute("SELECT `id`,`pricemax` FROM `"+symbol+"` ORDER BY `id` ASC LIMIT "+long+" OFFSET "+start)
mycursor.execute("SELECT * FROM (SELECT `id`,`pricemax` FROM `"+symbol+"` ORDER BY `id` DESC LIMIT "+long+" OFFSET "+start+") AS AAA ORDER BY `id` ASC")
myresult = mycursor.fetchall()
id=[el[0] for el in myresult]
price=[el[1] for el in myresult]
del myresult





percentOfPeaks = 0.005
widthOfPeaks = 60 #in baraie in ast ke shado haie zire 2 dagheii hazf shavad

from scipy import signal
#peaks = signal.find_peaks(price[0:int(long)/3],prominence=price[0]*percentOfPeaks)
peaks = signal.find_peaks(price,width=500,prominence=price[0]*percentOfPeaks)

print (peaks)
print ("\n")

#for i in peaks[1]['left_bases']:
#	print (str(id[i])+ " price:" + str(price[i]))
#print ("\n")
#for i in peaks[0]:
#	print (str(id[i])+ " price:" + str(price[i]))
#print ("\n")
#
#for i in peaks[1]['right_bases']:
#	print (str(id[i])+ " price:" + str(price[i]))
#
#print ("\n")

deltaDown,deltaIdDown,deltaUp,deltaIdUp = [],[],[],[]
for i in range(0,len(peaks[0])):
	mycursor.execute("UPDATE `btcusdt` SET `peak`='1'  WHERE `id`="+str(id[peaks[0][i]]))
	mydb.commit()
	mycursor.execute("UPDATE `btcusdt` SET `peak`='-1'  WHERE `id`="+str(id[peaks[1]['right_bases'][i]]))
	mydb.commit()
	#mycursor.execute("UPDATE `btcusdt` SET `peak`='-1'  WHERE `id`="+str(id[peaks[1]['left_bases'][i]]))
	#mydb.commit()
	deltaUp.append(price[peaks[0][i]]-price[peaks[1]['left_bases'][i]])
	deltaIdUp.append(peaks[0][i]-peaks[1]['left_bases'][i])
	deltaDown.append(price[peaks[0][i]]-price[peaks[1]['right_bases'][i]])
	deltaIdDown.append(peaks[1]['right_bases'][i]-peaks[0][i])

import numpy as np
import statistics as st
print ("number of peaks:"+ str(len(peaks[0])))
print("max of up peaks:"+str(max(deltaUp)))
print("max of Down peaks:"+str(max(deltaDown)))
print("average of up peaks:"+str(st.mean(deltaUp)))
print("average of Down peaks:"+str(st.mean(deltaDown)))
print("max of up width:"+str(max(deltaIdUp)))
print("max of Down width:"+str(max(deltaIdDown)))
print("average of up width:"+str(st.mean(deltaIdUp)))
print("average of Down width:"+str(st.mean(deltaIdDown)))
speadUp = np.array(deltaUp)/np.array(deltaIdUp)
speadDown = np.array(deltaDown)/np.array(deltaIdDown)
print("max of up spead of peaks:"+str(max(speadUp)))
print("max of Down spead of peaks:"+str(max(speadDown)))
print("average of up spead of peaks:"+str(st.mean(speadUp)))
print("average of Down spead of peaks:"+str(st.mean(speadDown)))



	


	
#def findPeaks(peaks,):
		
	
#for i in range(int(long)/3,int(long)):
	
	

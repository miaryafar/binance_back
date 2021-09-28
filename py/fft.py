
symbol = "btcusdt"
long = "300000"
start = "600000"

import mysql.connector

mydb = mysql.connector.connect(host="localhost",user="admin_binance",password="yUzT8iH42O",database="admin_binance")

mycursor = mydb.cursor()

mycursor.execute("SELECT * FROM (SELECT `id`,`pricemax` FROM `"+symbol+"` ORDER BY `id` DESC LIMIT "+long+" OFFSET "+start+") AS AAA ORDER BY `id` ASC")
myresult = mycursor.fetchall()

id=[el[0] for el in myresult]
price=[el[1] for el in myresult]
del myresult

import numpy as np
price = np.array(price)

from scipy.fft import fft
# Number of sample points
N = 300000
# sample spacing
T = 2
x = np.linspace(0.0, N*T, N)
y = np.sin(50.0 * 2.0*np.pi*x) + 0.5*np.sin(80.0 * 2.0*np.pi*x)
yf = fft(price-14750)
print(np.sort(2.0/N * np.abs(yf[0:N//2])))
xf = np.linspace(0.0, 1.0/(2.0*T), N//2)
"""
i=0
for val in 2.0/N * np.abs(yf[0:N//2]):
	if val > 1:
		print(i,":",val)
	i +=1
print (i)
"""
import matplotlib.pyplot as plt
cut = N//13
xf=xf[cut:]
yf=yf[cut:]

plt.plot(xf, 2.0/N * np.abs(yf[0:(N-2*cut)//2])) 
plt.grid()
plt.savefig("test.png")
plt.show()
"""
import numpy as np
from scipy.fft import fft
# Number of sample points
N = 600
# sample spacing
T = 1.0 / 800.0
x = np.linspace(0.0, N*T, N)
y = np.sin(50.0 * 2.0*np.pi*x) + 0.5*np.sin(80.0 * 2.0*np.pi*x)
yf = fft(y)
xf = np.linspace(0.0, 1.0/(2.0*T), N//2)

print(np.sort(2.0/N * np.abs(yf[0:N//2])))
import matplotlib.pyplot as plt
plt.plot(xf, 2.0/N * np.abs(yf[0:N//2]))
plt.grid()
plt.savefig("test.png")
plt.show()
"""
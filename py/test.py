import time  
f = open("test.txt", "a")
f.write("\n")
f.write("\n"+" salam1"+ "\n")
f.write(str(time.time()))

time.sleep(1)
f = open("test.txt", "a")
f.write("\n")
f.write("\n"+" salam1"+ "\n")
f.write(str(time.time()))

f.close()

#import subprocess
#subprocess.call("peak.py",shell=True)
import os
os.system('python3 peak.py')
#os.system('ls /root')
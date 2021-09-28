import time  
f = open("test2.txt", "a")

f.write("salam1")
f.write(time.time())
time.sleep(30)
f.write("khoda")
f.write(time.time())


f.close()


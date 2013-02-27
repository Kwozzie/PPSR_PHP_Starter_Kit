<?php
include_once("ppsrSoapClient.class.php")
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>PPSR Test</title>
    </head>
    <body>
        <h1>PPSR Ping Test</h1>
        <?php
        try{
            $client = new ppsrSoapClient();
            try{
                    $request_id = date("Ymdhisu").__LINE__;
                    $result = $client->Ping(array("PingRequest"=>array("CustomersRequestMessageId"=>$request_id)));
                    echo "<pre>";
                    print_r($result);
                    echo "</pre>";
            } catch (Exception $e) {
                    throw $e;
            }
        } catch (Exception $e) {
            echo "<h1>Debugging</h1>";
            $client->show_debug($e);
        }
        ?>
    </body>
</html>

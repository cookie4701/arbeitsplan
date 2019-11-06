<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../helper.class.php");

$data = file_get_contents("php://input");

$arr = json_decode($data);

if ( !isset($arr->username) || !isset($arr->passowrd) ) {
        $msg['code'] = -1;
        $msg['text'] = "Unable to login";
        echo json_encode($msg);
        die;
}

$username = $arr->username;
$password = $arr->password;

$helper = new Helper();

$stmt = $helper->getDatabaseConnection()->stmt_init();

$sql = "SELECT id, uname FROM aplan2_users WHERE uname = ? AND password = ?";
if ($stmt->prepare($sql) && $stmt->bind_param("ss", $username, $password) && $stmt->execute() && $stmt->bind_result($resId, $resUname) ) {
        if ($stmt->fetch() ) {
                // create web token
                use ReallySimpleJWT\Token;
                require("../../vendor/autoload.php");

                $payload = [
                        'id' => $resId,
                        'username' => resUname
                ];

                $secret = "foobar";
                try {
                        $token = Token::customPayload($paylod, $secret);
                        echo $token;
                } catch () {
                        echo 'not ok';
                }

        } else {
                $msg['code'] = -2;
                $msg['text'] = "Access denied";
        }
}



//$userid = $helper->TransformUserToId($_COOKIE["username"]);

if ( count($schedules) > 0 ) {
    http_response_code(200);
    echo json_encode($schedules);
} else {
    http_response_code(204);
    $schedules = array();
    $schedules['msg'] = "No data";
    echo json_encode($schedules);
} 




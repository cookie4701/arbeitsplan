<?php

//namespace Login;

use ReallySimpleJWT\Token;
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../cors.php");

cors();

require("../../helper.class.php");
require("../member.php");

$data = file_get_contents("php://input");

$arr = json_decode($data);

if ( !isset($arr->username) || !isset($arr->password) ) {
        $msg['code'] = -1;
        $msg['text'] = "Unable to login";
        echo json_encode($msg);
        die;
}

$username = $arr->username;
$password = $arr->password;

$helper = new Helper();

$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

$sql = "SELECT id, uname FROM aplan_users WHERE uname = ? AND password = ?";
if (! $stmt->prepare($sql) ) {
        echo "prepare failed";
        die;
}

if (! $stmt->bind_param("ss", $username, $password) ) {
        echo "bind failed";
        die;
}

if (! $stmt->execute() ) {
        echo "execute failed";
        die;
}

if ( $stmt->bind_result($resId, $resUname) ) {
        if ($stmt->fetch() ) {
                // create web token
                require("../../vendor/autoload.php");

                $payload = [
                        'id' => $resId,
                        'username' => $resUname,
                        'exp' => time() + 3600
                ];

                $secret = 'oobar&123ABC';
                try {
                        $token = Token::customPayload($payload, $secret);
                        $msg = array();
                        $msg['auth'] = "true";
                        $msg['user'] = $resUname;
                        $msg['token'] = $token;
			$msg['isModerator'] = isModerator($resId);
                        echo json_encode($msg);
                } catch (Exception $e) {
                        echo 'not ok... catch: ' . $e;
                }

        } else {
                $msg['code'] = -2;
                $msg['text'] = "Access denied";
        }
} else {
        echo "not ok " . $stmt->error . " " . $helper->getDatabaseConnection()->getDatabaseConnection()->error;
}



//$userid = $helper->TransformUserToId($_COOKIE["username"]);





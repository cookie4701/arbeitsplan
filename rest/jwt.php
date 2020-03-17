<?php
use ReallySimpleJWT\Parse;
use ReallySimpleJWT\Jwt;
use ReallySimpleJWT\Validate;
use ReallySimpleJWT\Encode;

require __DIR__ . '/../vendor/autoload.php';

function getJwtData() {
    $secret = "oobar&123ABC";

    $headers = apache_request_headers();
    $token = $headers['Authorization'];

    $jwt = new Jwt($token, $secret);

    $parse = new Parse($jwt, new Validate(), new Encode());

    $parsed = $parse->validate()->parse();

    // Return the token payload claims as an associative array.
    return $parsed->getPayload();

}

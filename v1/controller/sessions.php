<?php

include "../project/starter.php";

use Session\RequestHandler;

Starter::InitiateEssentials();

if (array_key_exists("sessionid", $_GET)) {
    $sessionId = $_GET["sessionid"];

    switch ($_SERVER["REQUEST_METHOD"]) {
        case 'DELETE':
            RequestHandler::logout($sessionId);
            break;
        
        case 'PATCH':
            RequestHandler::refresh($sessionId);
            break;
        
        default:
            $response->sendResponse(405, false, "Request method not allowed");
            break;
    }
}
else if (empty($_GET)) {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case 'POST':
            RequestHandler::createSession();
            break;
        
        default:
            $response->sendResponse(405, false, "Request method not allowed");
            break;
    }
}
else {
    $response->sendResponse(404, false, "Endpoint not found");
}

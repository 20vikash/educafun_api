<?php

include "../project/starter.php";

use Troops\RequestHandler;

Starter::InitiateEssentials();

switch ($_SERVER["REQUEST_METHOD"]) {
    case 'GET':
        if (isset($_GET["uid"])) {
            $uid = $_GET["uid"];
            RequestHandler::getTroops($uid);
        } else {
            $response->sendResponse(400, false, "uid parameter missing from the url");
        }
        break;
    
    case 'POST':
        RequestHandler::postTroop();
        break;
    
    default:
        $response->sendResponse(405, false, "Invalid request method");
        break;
}

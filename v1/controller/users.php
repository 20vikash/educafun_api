<?php

include "../project/starter.php";

use SignUp\RequestHandler;

Starter::InitiateEssentials();

switch ($_SERVER["REQUEST_METHOD"]) {
    case 'POST':
        RequestHandler::signUp();
        break;
    
    default:
        $response->sendResponse(405, false, "Request method not allowed");
        break;
}

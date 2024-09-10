<?php

include "../project/starter.php";

use UserGame\RequestHandler;

Starter::InitiateEssentials();

switch ($_SERVER["REQUEST_METHOD"]) {
    case 'GET':
        RequestHandler::getUserGameData();
        break;
    
    case 'PATCH':
        RequestHandler::updatePointsAndLeague();
        break;
    
    case 'POST':
        RequestHandler::postUserGameData();
        break;
    
    default:
        $response->sendResponse(405, false, "Request method not allowed");
        break;
}

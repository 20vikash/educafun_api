<?php

include_once "../model/Response.php";
include_once "../controller/db.php";
include_once "../model/User.php";
include_once "../controller/utils/signup/RequestHandler.php";
include_once "../controller/utils/session/RequestHandler.php";
include_once "helper.php";

global $writeDB;
global $readDB;
global $response;

class Starter {
    public static function InitiateEssentials() {
        global $writeDB, $readDB, $response;

        $response = new Response();

        try {
            $writeDB = DB::connectWriteDB();
            $readDB = DB::connectReadDB();
        } catch (PDOException $e) {
            error_log("Connection error" . $e, 0);

            $response->sendResponse(500, false, "Database error");
        }
    }
}

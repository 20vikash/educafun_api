<?php

class Helper
{
    public static function contentTypeIsJSON() {
        return $_SERVER["CONTENT_TYPE"] == "application/json";
    }

    public static function checkValidJSON($json) {
        return json_decode($json, true);
    }

    public static function getRawBody() {
        return file_get_contents("php://input");
    }

    public static function getAuthHeader() {
        global $response;

        if (!isset($_SERVER["HTTP_AUTHORIZATION"])) {
            $response->sendResponse(401, false, "Access token is missing from the header");
        }

        if (strlen($_SERVER["HTTP_AUTHORIZATION"]) < 1) {
            $response->sendResponse(401, false, "Access token cannot be blank");
        }

        return $_SERVER["HTTP_AUTHORIZATION"];
    }

    public static function isAuthorized() {
        global $response, $writeDB;

        $access_token = Helper::getAuthHeader();

        try {
            $query = $writeDB->prepare("SELECT uid, accesstokenexpiry, useractive, loginattempts FROM sessions, users WHERE sessions.uid = users.id and accesstoken = :accesstoken");
            $query->bindParam(":accesstoken", $access_token, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount == 0) {
                $response->sendResponse(401, false, "Invalid access token");
            }

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $returned_uid = $row["uid"];
            $returned_accesstokenexpiry = $row["accesstokenexpiry"];
            $returned_useractive = $row["useractive"];
            $returned_loginattempts = $row["loginattempts"];

            if ($returned_useractive !== "Y") {
                $response->sendResponse(401, false, "User account not active");
            }

            if ($returned_loginattempts >= 3) {
                $response->sendResponse(401, false, "User account is currently locked out");
            }

            if (strtotime($returned_accesstokenexpiry) < time()) {
                $response->sendResponse(401, false, "Access token expired");
            }

            return $returned_uid;
        }
        catch (PDOException $e) {
            $response->sendResponse(500, true, "There was an issue authenticating - please try again");
        }
    }
}

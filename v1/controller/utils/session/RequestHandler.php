<?php

namespace Session;

use Helper;
use PDO;
use PDOException;
use User;
use UserException;

class RequestHandler
{
    public static function createSession() {
        global $response, $writeDB;

        sleep(1);

        if (!Helper::contentTypeIsJSON()) {
            $response->sendResponse(405, false, "Content type header not set to JSON");
        }

        $rawPOSTData = Helper::getRawBody();

        if (!$jsonData = Helper::checkValidJSON($rawPOSTData)) {
            $response->sendResponse(400, false, "Request body is not a valid JSON");
        }

        if (!isset($jsonData["username"]) || !isset($jsonData["password"])) {
            $message = array();

            if (!isset($jsonData["username"])) {
                $message[] = "username is not set";
            }
            if (!isset($jsonData["password"])) {
                $message[] = "password is not set";
            }

            $response->sendResponse(400, false, $message);
        }

        try {
            $user = new User(null, null, $jsonData["username"], $jsonData["password"], null, null);
        }
        catch (UserException $e) {
            $response->sendResponse(400, false, $e->getMessage());
        }

        try {
            $username = $user->getUsername();
            $password = $user->getPassword();

            $query = $writeDB->prepare("SELECT * FROM users WHERE username = :username");
            $query->bindParam(":username", $username, PDO::PARAM_STR);
            $query->execute();

            $rowCount = $query->rowCount();
            if ($rowCount === 0) {
                $response->sendResponse(401, false, "Username or password is incorrect");
            }

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $returned_id = $row["id"];
            $returned_password = $row["password"];
            $returned_useractive = $row["useractive"];
            $returned_loginattempts = $row["loginattempts"];

            if ($returned_useractive !== "Y") {
                $response->sendResponse(401, false, "User account not active"); 
            }

            if ($returned_loginattempts >= 3) {
                $response->sendResponse(401, false, "User account is currently locked out");
            }

            if (!password_verify($password, $returned_password)) {
                $query = $writeDB->prepare("UPDATE users SET loginattempts = loginattempts + 1 where id = :id");
                $query->bindParam(":id", $returned_id, PDO::PARAM_INT);
                $query->execute();

                $response->sendResponse(401, false, "Username or password is incorrect");
            }

            $accessToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24).time()));
            $refreshToken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24).time()));

            $accessTokenExpirySeconds = 1200;
            $refreshTokenExpirySeconds = 1209600;
        }
        catch (PDOException $e) {
            $response->sendResponse(500, false, "There was an issue logging in");
        }

        try {
            $writeDB->beginTransaction();

            $query = $writeDB->prepare("UPDATE users SET loginattempts = 0 where id = :id");
            $query->bindParam(":id", $returned_id, PDO::PARAM_INT);
            $query->execute();

            $query = $writeDB->prepare("INSERT INTO sessions (uid, accesstoken, accesstokenexpiry, refreshtoken, refreshtokenexpiry) VALUES(:uid, :accessToken, date_add(NOW(), INTERVAL :accessTokenExpiry SECOND), :refreshToken, date_add(NOW(), INTERVAL :refreshTokenExpiry SECOND))");
            $query->bindParam(":uid", $returned_id, PDO::PARAM_INT);
            $query->bindParam(":accessToken", $accessToken, PDO::PARAM_STR);
            $query->bindParam(":accessTokenExpiry", $accessTokenExpirySeconds, PDO::PARAM_INT);
            $query->bindParam(":refreshToken", $refreshToken, PDO::PARAM_STR);
            $query->bindParam(":refreshTokenExpiry", $refreshTokenExpirySeconds, PDO::PARAM_INT);

            $query->execute();

            $lastSessionId = $writeDB->lastInsertId();

            $writeDB->commit();

            $returnData = array();
            
            $returnData["session_id"] = intval($lastSessionId);
            $returnData["uid"] = $returned_id;
            $returnData["access_token"] = $accessToken;
            $returnData["access_token_expires_in"] = $accessTokenExpirySeconds;
            $returnData["refresh_token"] = $refreshToken;
            $returnData["refresh_token_expires_in"] = $refreshTokenExpirySeconds;

            $response->sendResponse(201, true, null, $returnData);
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            $writeDB->rollBack();
            $response->sendResponse(500, false, "There was an issue logging in - Please try again later");
        }
    }

    public static function logout($sessionId) {
        global $response, $writeDB;
        
        if ($sessionId == '') {
            $response->sendResponse(400, false, "Session ID cannot be blank");
        }
        if (!is_numeric($sessionId)) {
            $response->sendResponse(400, false, "Session ID must be numeric");
        }

        $accessToken = Helper::getAuthHeader();

        try {
            $query = $writeDB->prepare("DELETE FROM sessions WHERE id=:sessionid and accesstoken=:accesstoken");
            $query->bindParam(":sessionid", $sessionId, PDO::PARAM_INT);
            $query->bindParam(":accesstoken", $accessToken, PDO::PARAM_STR);

            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount == 0) {
                $response->sendResponse(400, false, "Failed to log out of this session using access token provided");
            }

            $returnData = array();

            $returnData["session_id"] = intval($sessionId);

            $response->sendResponse(200, true, "Logged out", $returnData);
        }
        catch (PDOException $e) {
            $response->sendResponse(500, false, "There was an issue logging out - Please try again");
        }
    }

    public static function refresh($sessionId) {
        global $response, $writeDB;

        if (!Helper::contentTypeIsJSON()) {
            $response->sendResponse(405, false, "Content type header not set to JSON");
        }

        $rawPOSTData = Helper::getRawBody();

        if (!$jsonData = Helper::checkValidJSON($rawPOSTData)) {
            $response->sendResponse(400, false, "Request body is not a valid JSON");
        }

        if (!isset($jsonData["refresh_token"]) || strlen($jsonData["refresh_token"]) < 1) {
            $message = array();

            if (!isset($jsonData["refresh_token"])) {
                $message[] = "Refresh token not supplied";
            }
            if (strlen($jsonData["refresh_token"]) < 1) {
                $message[] = "Refresh token cannot be blank";
            }

            $response->sendResponse(400, false, $message);
        }

        try {
            $refresh_token = $jsonData["refresh_token"];
            $access_token = Helper::getAuthHeader();

            $query = $writeDB->prepare("
            SELECT sessions.id as sessionid, sessions.uid as uid, accesstoken, refreshtoken, useractive, loginattempts, accesstokenexpiry, refreshtokenexpiry FROM sessions, users WHERE users.id = sessions.uid and sessions.id = :sessionid and sessions.accesstoken = :accesstoken and sessions.refreshtoken = :refreshtoken
            ");

            $query->bindParam(":sessionid", $sessionId, PDO::PARAM_INT);
            $query->bindParam(":accesstoken", $access_token, PDO::PARAM_STR);
            $query->bindParam(":refreshtoken", $refresh_token, PDO::PARAM_STR);

            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount == 0) {
                $response->sendResponse(400, false, "Access token or refresh token is incorrect for session id");
            }

            $row = $query->fetch(PDO::FETCH_ASSOC);

            $returned_sessionid = $row["sessionid"];
            $returned_uid = $row["uid"];
            $returned_accesstoken = $row["accesstoken"];
            $returned_refreshtoken = $row["refreshtoken"];
            $returned_useractive = $row["useractive"];
            $returned_loginattempts = $row["loginattempts"];
            $returned_accesstokenexpiry = $row["accesstokenexpiry"];
            $returned_refreshtokenexpiry = $row["refreshtokenexpiry"];

            if ($returned_useractive !== "Y") {
                $response->sendResponse(401, false, "User account it not active");
            }
            if ($returned_loginattempts >= 3) {
                $response->sendResponse(401, false, "User account is currently locked out");
            }
            if (strtotime($returned_refreshtokenexpiry) < time()) {
                $response->sendResponse(401, false, "Refresh token has expired - Please log in again");
            }

            $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24).time()));
            $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24).time()));

            $access_token_expiry_seconds = 1200;
            $refresh_token_expiry_seconds = 1209600;

            $query = $writeDB->prepare("UPDATE sessions SET accesstoken = :accesstoken, accesstokenexpiry = DATE_ADD(NOW(), INTERVAL :accesstokenexpiry SECOND), refreshtoken = :refreshtoken, refreshtokenexpiry = DATE_ADD(NOW(), INTERVAL :refreshtokenexpiry SECOND) WHERE id = :sessionid and uid = :uid and accesstoken = :returned_accesstoken and refreshtoken = :returned_refreshtoken");

            $query->bindParam("uid", $returned_uid, PDO::PARAM_INT);
            $query->bindParam(":sessionid", $returned_sessionid, PDO::PARAM_INT);
            $query->bindParam(":accesstoken", $accesstoken, PDO::PARAM_STR);
            $query->bindParam(":accesstokenexpiry", $access_token_expiry_seconds, PDO::PARAM_INT);
            $query->bindParam(":refreshtoken", $refreshtoken, PDO::PARAM_STR);
            $query->bindParam("refreshtokenexpiry", $refresh_token_expiry_seconds, PDO::PARAM_INT);
            $query->bindParam("returned_accesstoken", $returned_accesstoken, PDO::PARAM_STR);
            $query->bindParam("returned_refreshtoken", $returned_refreshtoken, PDO::PARAM_STR);

            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount == 0) {
                $response->sendResponse(401, false, "Access token could not be refreshed - Please log in again");
            }

            $returnData = array();

            $returnData["session_id"] = $returned_sessionid;
            $returnData["access_token"] = $accesstoken;
            $returnData["access_token_expires_in"] = $access_token_expiry_seconds;
            $returnData["refresh_token"] = $refreshtoken;
            $returnData["refresh_token_expires_in"] = $refresh_token_expiry_seconds;

            $response->sendResponse(200, true, "Token refreshed", $returnData);
        }
        catch (PDOException $e) {
            print($e->getMessage());
            $response->sendResponse(500, false, "There was an issue refreshing access token - Please login again");
        }
    }
}

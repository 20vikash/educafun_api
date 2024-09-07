<?php

namespace SignUp;

use PDOException;
use PDO;
use User;
use UserException;

class RequestHandler
{
    public static function signUp() {
        global $response, $writeDB;

        if ($_SERVER['CONTENT_TYPE'] !== "application/json") {
            $response->sendResponse(400, false, "Content type not set to JSON");
        }

        $rawPOSTData = file_get_contents("php://input");
        if (!$jsonData = json_decode($rawPOSTData, true)) {
            $response->sendResponse(400, false, "Request body is not valid JSON");
        }

        if (!isset($jsonData["fullname"]) || !isset($jsonData["username"]) || !isset($jsonData["password"])) {
            $message = [];

            if (!isset($jsonData["fullname"])) {
                $message[] = "fullname not provided";
            }
            if (!isset($jsonData["username"])) {
                $message[] = "username not provided";
            }
            if (!isset($jsonData["password"])) {
                $message[] = "password not provided";
            }
        }

        $user = new User(
            null,
            $jsonData["fullname"],
            $jsonData["username"],
            $jsonData["password"],
            null,
            null
        );

        try {
            $query = $writeDB->prepare("INSERT INTO users(fullname, username, password) VALUES(:fullname, :username, :password)");

            $fullName = $user->getFullname();
            $userName = $user->getUsername();
            $password = $user->getPassword();

            $password = password_hash($password, PASSWORD_DEFAULT);

            $query->bindParam(":fullname", $fullName, PDO::PARAM_STR);
            $query->bindParam(":username", $userName, PDO::PARAM_STR);
            $query->bindParam(":password", $password, PDO::PARAM_STR);

            $query->execute();

            $rowCount = $query->rowCount();

            if ($rowCount < 1) {
                $response->sendResponse(500, false, "There was an issue creating an user account - please try again");
            }

            $lastUserId = $writeDB->lastInsertId();

            $returnData = array();
            $returnData["user_id"] = $lastUserId;
            $returnData["fullname"] = $jsonData["fullname"];
            $returnData["username"] = $jsonData["username"];

            $response->sendResponse(201, true, "User successfully created", $returnData);
        }
        catch (UserException $e) {
            $response->sendResponse(400, false, $e->getMessage());
        }
        catch (PDOException $e) {
            if (str_contains($e->getMessage(), "Duplicate entry")) {
                $response->sendResponse(409, false, "User already exists");
            }
            $response->sendResponse(500, false, "There was an issue creating an user account - please try again");
        }

        $response->sendResponse(400, false, $message);
    }
}

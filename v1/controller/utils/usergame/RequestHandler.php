<?php

namespace UserGame;

use Helper;
use PDO;
use PDOException;
use UserGameException;
use UserGame;

class RequestHandler
{
    public static function getUserGameData() {
        global $response, $readDB;

        $uid = Helper::isAuthorized();

        try {
            $query = $readDB->prepare("SELECT * FROM user_game WHERE `uid`=:uid");
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $row_count = $query->rowCount();

            if ($row_count == 0) {
                $response->sendResponse(404, false, "No user game data found");
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $usergame = new UserGame($row["id"], $row["uid"], $row["points"], $row["league"], $row["clan"], $row["online"], $row["last_game"]);

                $taskArray[] = $usergame->returnUserGameAsArray();
            }

            $returnData = array();
            $returnData["rows_returned"] = $row_count;
            $returnData["user_game_data"] = $taskArray;

            $response->sendResponse(200, true, null, $returnData, true);
        } catch (PDOException $e) {
            $response->sendResponse(500, false, "Failed to get data");
            exit;
        } catch (UserGameException $e) {
            $response->sendResponse(500, false, $e->getMessage());
        }
    }

    public static function postUserGameData() {
        global $response, $writeDB, $readDB;

        $uid = Helper::isAuthorized();

        try {
            $query = $writeDB->prepare("INSERT INTO user_game(`uid`) VALUES (:uid)");
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $query = $readDB->prepare("SELECT * FROM user_game WHERE `uid`=:uid");
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $row_count = $query->rowCount();
            if ($row_count == 0) {
                $response->sendResponse(404, false, "No game_data found with this uid");
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $user_game = new UserGame($row["id"], $row["uid"], $row["points"], $row["league"], $row["clan"], $row["online"], $row["last_game"]);
                $userGameData[] = $user_game->returnUserGameAsArray();
            }

            $returnData = array();
            $returnData["rows_returned"] = $row_count;
            $returnData["user_game_data"] = $userGameData;

            $response->sendResponse(201, true, "User game data created", $returnData);
        } catch (PDOException $e) {
            $response->sendResponse(500, false, "Cannot post userGame data");
        } catch (UserGameException $e) {
            $response->sendResponse(500, false, $e->getMessage());
        }
    }

    public static function updatePointsAndLeague() {
        global $response, $writeDB, $readDB;

        if ($_SERVER["CONTENT_TYPE"] !== "application/json") {
            $response->sendResponse(400, false, "Content type header not set to json");
        }
        
        $rawPATCHData = file_get_contents("php://input");
        
        if (!$jsonData = json_decode($rawPATCHData, true)) {
            $response->sendResponse(400, false, "Request body is not valid JSON");
        }

        if (isset($jsonData["points"])) {
            $points = $jsonData["points"];
        }

        $uid = Helper::isAuthorized();

        try {
            $query = $readDB->prepare("SELECT * FROM user_game WHERE `uid`=:uid");
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $row_count = $query->rowCount();

            if ($row_count == 0) {
                $response->sendResponse("Something went wrong");
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $user_game = new UserGame($row["id"], $row["uid"], $row["points"], $row["league"], $row["clan"], $row["online"], $row["last_game"]);
            }

            $returned_points = $user_game->getPoints();
            $points += $returned_points;
            $league = $user_game->getLeague();

            if ($points >= 1000 && $points < 5000) {
                $league = "Silver";
            }
            else if ($points >= 5000 && $points < 15000) {
                $league = "Gold";
            }
            else if ($points >= 15000) {
                $league = "Master";
            }

            $query = $writeDB->prepare("UPDATE user_game SET `points`=`points`+:points, `league`=:league WHERE `uid`=:uid");
            $query->bindParam(":points", $points, PDO::PARAM_INT);
            $query->bindParam(":league", $league, PDO::PARAM_STR);
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $row_count = $query->rowCount();

            if ($row_count == 0) {
                $response->sendResponse(404, false, "No record to update");
            }

            $user_game->setPoints($points);
            $user_game->setLeague($league);
            $user_game_data[] = $user_game->returnUserGameAsArray();

            $returnData = array();
            $returnData["rows_returned"] = $row_count;
            $returnData["user_game_data"] = $user_game_data;

            $response->sendResponse(200, true, "Updated points and league successfully", $returnData);

        } catch (PDOException $e) {
            $response->sendResponse(500, false, "Failed to update points");
        } catch (UserGameException $e) {
            $response->sendResponse(500, false, $e->getMessage());
        }
    }
}

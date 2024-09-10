<?php

namespace Troops;

use Helper;
use PDO;
use PDOException;
use TroopException;
use Troop;

class RequestHandler
{
    public static function postTroop() {
        global $response, $writeDB, $readDB;

        if ($_SERVER["CONTENT_TYPE"] !== "application/json") {
            $response->sendResponse(400, false, "Content type header not set to json");
        }
        
        $rawPATCHData = file_get_contents("php://input");
        
        if (!$jsonData = json_decode($rawPATCHData, true)) {
            $response->sendResponse(400, false, "Request body is not valid JSON");
        }

        $uid = Helper::isAuthorized();

        try {
            $sql = "INSERT INTO troops (uid, question, c1, c2, c3, c4, correct_option)
                    VALUES (:uid, :question, :c1, :c2, :c3, :c4, :correct_option)";

            $query = $writeDB->prepare($sql);

            $query->bindParam(":uid", $uid, PDO::PARAM_INT);

            foreach ($jsonData as $index => $data) {
                $query->bindParam(":question", $data["question"], PDO::PARAM_STR);
                $query->bindParam(":c1", $data["choice"][0], PDO::PARAM_STR);
                $query->bindParam(":c2", $data["choice"][1], PDO::PARAM_STR);
                $query->bindParam(":c3", $data["choice"][2], PDO::PARAM_STR);
                $query->bindParam(":c4", $data["choice"][3], PDO::PARAM_STR);
                $query->bindParam(":correct_option", $data["correct_answer"], PDO::PARAM_INT);

                $query->execute();
            }

            $query = $readDB->prepare("SELECT id, uid, question, c1, c2, c3, c4, correct_option FROM troops WHERE uid = :uid GROUP BY id, uid, question, c1, c2, c3, c4, correct_option;");
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $row_count = $query->rowCount();
            
            if ($row_count == 0) {
                $response->sendResponse(404, false, "No troops found");
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $choices = [$row["c1"], $row["c2"], $row["c3"], $row["c4"]];
                $troop = new Troop($row["id"], $row["uid"], $row["question"], $choices, $row["correct_option"]);

                $troopData[] = $troop->returnTroopAsArray();
            }

            $returnData = array();
            $returnData["rows_returned"] = $row_count;
            $returnData["troops_data"] = $troopData;

            $response->sendResponse(201, true, "Successfully created troops", $returnData);
        } catch (PDOException $e) {
            $response->sendResponse(500, false, "Cannot post troop, {$e->getMessage()}");
        } catch (TroopException $e) {
            $response->sendResponse(500, false, $e->getMessage());
        }
    }

    public static function getTroops($uid) {
        global $response, $readDB;

        Helper::isAuthorized();

        try {
            $query = $readDB->prepare("SELECT id, uid, question, c1, c2, c3, c4, correct_option FROM troops WHERE uid = :uid GROUP BY id, uid, question, c1, c2, c3, c4, correct_option;");
            $query->bindParam(":uid", $uid, PDO::PARAM_INT);
            $query->execute();

            $row_count = $query->rowCount();
            
            if ($row_count == 0) {
                $response->sendResponse(404, false, "No troops found");
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $choices = [$row["c1"], $row["c2"], $row["c3"], $row["c4"]];
                $troop = new Troop($row["id"], $row["uid"], $row["question"], $choices, $row["correct_option"]);

                $troopData[] = $troop->returnTroopAsArray();
            }

            $returnData = array();
            $returnData["rows_returned"] = $row_count;
            $returnData["troops_data"] = $troopData;

            $response->sendResponse(200, true, null, $returnData);
        } catch (PDOException $e) {
            $response->sendResponse(500, false, "Failed to retrieve troops");
        } catch (TroopException $e) {
            $response->sendResponse(500, false, $e->getMessage());
        }
    }
}

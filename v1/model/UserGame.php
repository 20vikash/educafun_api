<?php

class UserGameException extends Exception { }

class UserGame
{
    private $id;
    private $uid;
    private $points;
    private $league;
    private $clan;
    private $online;
    private $last_game;

    public function __construct($id, $uid, $points, $league, $clan, $online, $last_game) {
        $this->setID($id);
        $this->setUID($uid);
        $this->setPoints($points);
        $this->setLeague($league);
        $this->setClan($clan);
        $this->setOnline($online);
        $this->setLastGame($last_game);
    }

    public function getId(): Int {
        return $this->id;
    }

    public function getUID(): Int {
        return $this->uid;
    }

    public function getPoints(): Int {
        return $this->points;
    }

    public function getLeague(): String {
        return $this->league;
    }

    public function getClan(): String {
        return $this->clan;
    }

    public function getOnline(): String {
        return $this->online;
    }

    public function getLastGame(): String {
        return $this->last_game;
    }

    public function setUID($uid) {
        $this->uid = $uid;
    }

    public function setID($id) {
        if (($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->id !== null)) {
            throw new UserGameException("Task id error");
        }

        $this->id = $id;
    }
    
    public function setPoints($points = 0) {
        if ($points < 0) {
            throw new UserGameException("Points cannot be less than 0");
        }

        $this->points = $points;
    }

    public function setLeague($league = "Bronze") {
        $leagues = ["Bronze", "Silver", "Gold", "Master"];

        if (!in_array($league, $leagues)) {
            throw new UserGameException("Invalid league name");
        }

        $this->league = $league;
    }

    public function setClan($clan = null) {
        if (strlen($clan) < 1 and $clan != null) {
            throw new UserGameException("Invalid clan name");
        }

        $this->clan = $clan;
    }

    public function setOnline($online = "offline") {
        $options = ["online", "offline"];

        if (!in_array($online, $options)) {
            throw new UserGameException("Invalid input");
        }

        $this->online = $online;
    }

    public function setLastGame($lastGame = null) {
        $options = ["win", "lose"];

        if (!in_array($lastGame, $options) and $lastGame != null) {
            throw new UserGameException("Invalid input");
        }

        $this->last_game = $lastGame;
    }

    public function returnUserGameAsArray() {
        $user_game = Array();

        $user_game["id"] = $this->getID();
        $user_game["uid"] = $this->getUID();
        $user_game["points"] = $this->getPoints();
        $user_game["league"] = $this->getLeague();
        $user_game["clan"] = $this->getClan();
        $user_game["online"] = $this->getOnline();
        $user_game["last_game"] = $this->getLastGame();
        

        return $user_game;
    }
}

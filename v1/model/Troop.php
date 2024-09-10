<?php

class TroopException extends Exception { }

class Troop
{
    private $id;
    private $uid;
    private $question;
    private $choices;
    private $correct_answer;

    public function __construct($id, $uid, $question, $choices, $correct_answer) {
        $this->setID($id);
        $this->setUID($uid);
        $this->setQuestion($question);
        $this->setChoices($choices);
        $this->setCorrectAnswer($correct_answer);
    }

    public function getID(): Int {
        return $this->id;
    }

    public function getUID(): Int {
        return $this->uid;
    }

    public function getQuestion(): String {
        return $this->question;
    }

    public function getChoices(): array {
        return $this->choices;
    }

    public function getCorrecAnswer(): Int {
        return $this->correct_answer;
    }

    public function setID($id) {
        if (($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->id !== null)) {
            throw new UserGameException("Task id error");
        }

        $this->id = $id;
    }

    public function setUID($uid) {
        $this->uid = $uid;
    }

    public function setQuestion($question) {
        if (strlen($question) == 0) {
            throw new TroopException("Invalid question");
        }
        
        $this->question = $question;
    }

    public function setChoices($choices) {
        if (!is_array($choices) || count($choices) != 4) {
            throw new TroopException("Invalid amount of choices");
        }

        $this->choices = $choices;
    }

    public function setCorrectAnswer($correct_answer) {
        if ($correct_answer < 0 || $correct_answer > 4) {
            throw new TroopException("Invalid correct answer");
        }

        $this->correct_answer = $correct_answer;
    }

    public function returnTroopAsArray() {
        $user_game = Array();

        $user_game["id"] = $this->getID();
        $user_game["uid"] = $this->getUID();
        $user_game["question"] = $this->getQuestion();
        $user_game["choices"] = $this->getChoices();
        $user_game["correct_answer"] = $this->getCorrecAnswer();
        
        return $user_game;
    }
}

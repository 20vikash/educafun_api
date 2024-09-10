<?php

class UserException extends Exception { }

class User
{
    private $id;
    private $fullname;
    private $username;
    private $password;
    private $useractive;
    private $loginattempts;

    public function __construct($id, $fullname, $username, $password, $useractive, $loginattempts) {
        $this->setID($id);
        $this->setFullname($fullname);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setUseractive($useractive);
        $this->setLoginattempts($loginattempts);
    }

    public function setID($id) {
        if (($id !== null) && (!is_numeric($id) || $id <= 0 || $id > 9223372036854775807 || $this->id !== null)) {
            throw new UserException("User id error");
        }

        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function getFullname(): String {
        return $this->fullname;
    }

    public function getUsername(): String {
        return $this->username;
    }

    public function getPassword(): String {
        return $this->password;
    }

    public function getUseractive(): String {
        return $this->useractive;
    }

    public function getLoginattempts(): Int {
        return $this->loginattempts;
    }

    public function setFullname($fullName) {
        if (($fullName !== null) && strlen($fullName) < 1) {
            throw new UserException("fullname cannot be blank");
        }
        else if (($fullName !== null) && strlen($fullName) > 255) {
            throw new UserException("fullname cannot be more than 255 characters");
        }
        $fullName = ($fullName !== null) ? trim($fullName) : null;

        $this->fullname = $fullName;
    }

    public function setUsername($username) {
        if (strlen($username) < 1) {
            throw new UserException("username cannot be blank");
        }
        else if (strlen($username) > 255) {
            throw new UserException("username cannot be more than 255 characters");
        }
        $username = trim($username);

        $this->username = $username;
    }

    public function setPassword($password) {
        if (strlen($password) < 1) {
            throw new UserException("password cannot be blank");
        }
        else if (strlen($password) > 255) {
            throw new UserException("password cannot be more than 255 characters");
        }

        $this->password = $password;
    }

    public function setUseractive($active) {
        if (($active !== null) && (strtoupper($active) !== "Y" && strtoupper($active) !== "N")) {
            throw new UserException("Useractive must be Y or N");
        }

        $this->useractive = $active;
    }

    public function setLoginattempts($attempts) {
        if (($attempts !== null) && ($attempts > 3)) {
            throw new UserException("Login attempts exceeded");
        }

        $this->loginattempts = $attempts;
    }
}

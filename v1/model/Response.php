<?php

class Response
{
    // Response variables
    private $_success, $_httpStatusCode;
    private $_messages = [];
    private $_data;

    // Internal process variables.
    private $_toCache = false;
    private $_responseData = [];

    // Functions
    public function setSuccess($success) {
        $this->_success = $success;
    }

    public function setHttpStatusCode($httpStatusCode) {
        $this->_httpStatusCode = $httpStatusCode;
    }

    public function addMessage($message) {
        $this->_messages[] = $message;
    }

    public function setData($data) {
        $this->_data = $data;
    }

    public function toCache($toCache) {
        $this->_toCache = $toCache;
    }

    private function send() {
        header("Content-type: application/json;charset=utf-8");

        if ($this->_toCache) {
            header("Cache-control: max-age=60");
        } else {
            header("Cache-control: no-cache, no-store");
        }

        if (($this->_success !== false && $this->_success !== true) || !(is_numeric($this->_httpStatusCode))) {
            http_response_code(500);

            $this->_responseData["statusCode"] = 500;
            $this->_responseData["success"] = false;

            $this->addMessage("Response creation error..");
            $this->_responseData["messages"] = $this->_messages;
        } else {
            http_response_code($this->_httpStatusCode);

            $this->_responseData["statusCode"] = $this->_httpStatusCode;
            $this->_responseData["success"] = $this->_success;

            $this->_responseData["messages"] = $this->_messages;
            $this->_responseData["Data"] = $this->_data;
        }

        echo json_encode($this->_responseData);
    }

    public function sendResponse($statusCode, $success, $message=null, $data=null, $toCache=false) {
        $this->setHttpStatusCode($statusCode);
        $this->setSuccess($success);
        if ($message != null) {
            $this->addMessage($message);
        }
        else if (is_array($message)) {
            for ($i=0; $i < count($message); $i++) { 
                $this->addMessage($message[$i]);
            }
        }
        $this->setData($data);
        $this->toCache($toCache);

        $this->send();
        exit;
    }
}

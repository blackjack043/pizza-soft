<?php

class Manager {
    function checkAuthKey() {
        if (!isset($_SERVER['HTTP_X_AUTH_KEY']) || $_SERVER['HTTP_X_AUTH_KEY'] !== AUTH_KEY) {
            return false;   
        }
        
        return true;
    }
}
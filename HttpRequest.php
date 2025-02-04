<?php
class HttpRequest {
    public static function getJsonBody() {
        return json_decode(file_get_contents('php://input'), true);
    }
}
<?php

require 'AltoRouter.php';
require 'OrderController.php';
// Заготовка для простого REST API для управления пиццерией

header('Content-Type: application/json');

// Уникальный ключ авторизации
define('AUTH_KEY', getenv('AUTH_KEY') ?: 'qwerty123');

$router = new AltoRouter();

$router->map('GET', '/orders',  function() {
    (new OrderController())->listOrders();
});

$router->map('GET', '/orders/[i:id]', function($id) {
    (new OrderController())->getOrder($id);
});

$router->map('POST', '/orders', function() {
    (new OrderController())->createOrder();
});

$router->map('POST', '/orders/[i:id]/items', function($id) {
    (new OrderController())->addItemsToOrder($id);
});

$router->map('POST', '/orders/[i:id]/done', function($id) {
    (new OrderController())->markOrderAsDone($id);
});


$match = $router->match();

if ($match) {
    call_user_func_array($match['target'], $match['params']);
} else {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    echo json_encode(["error" => "Not Found"]);
}
die();





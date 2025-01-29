<?php

require 'AltoRouter.php';
// Заготовка для простого REST API для управления пиццерией

header('Content-Type: application/json');

// Уникальный ключ авторизации
define('AUTH_KEY', 'qwerty123');

$router = new AltoRouter();

$router->map('GET', '/orders',  function() {
    listOrders();
});

$router->map('GET', '/orders/[i:id]', function($id) {
    getOrder($id);
});

$router->map('POST', '/orders', function() {
    createOrder();
});

$router->map('POST', '/orders/[i:id]/items', function($id) {
    addItemsToOrder($id);
});

$router->map('POST', '/orders/[i:id]/done', function($id) {
    markOrderAsDone($id);
});


$match = $router->match();

if ($match) {
    call_user_func_array($match['target'], $match['params']);
} else {
    header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
    echo json_encode(["error" => "Not Found"]);
}
die();


function createOrder() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid items']);
        return;
    }

    // Создание подключения
    $conn = getBdConnection();
  
    $insert_sql = "INSERT INTO orders (items, done) VALUES ('" . json_encode($data['items']) . "', 0)";

    if ($conn->query($insert_sql) === TRUE) {
        // Получение ID последней вставленной записи
        $order_id = $conn->insert_id;

        // Формирование ответа
        $response = [
            'order_id' => $order_id,
            'items' => $data['items'],
            'done' => false
        ];
        http_response_code(201);
        echo json_encode($response);
    } else {
        // Ошибка при выполнении запроса
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order', 'details' => $conn->error]);
    }

    http_response_code(201);
}

function addItemsToOrder($orderId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($orderId)) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        return;
    }

    if (!is_array($data) || empty($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid items']);
        return;
    }

    // Создание подключения
    $conn = getBdConnection();

    $selectById = "SELECT * FROM orders WHERE id = '$orderId'";
    $result = $conn->query($selectById);
    $items = [];
    $done = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $items = $row["items"];
        $done = $row["done"];
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        die();
    }
    
    $result = array_merge(json_decode($items), $data["items"]);
   
    $insert_sql = "UPDATE orders SET items = '" . json_encode($result) . "' WHERE id = $orderId";

    if ($conn->query($insert_sql) === TRUE) {
        $res = [
            "order_id" => $orderId,
            "items" => json_encode($result),
            "done" => 0,
        ];
        echo json_encode($res);
        http_response_code(200);
    } else {
        http_response_code(500);
    }
}

function markOrderAsDone($orderId) {
    if (!isset($_SERVER['HTTP_X_AUTH_KEY']) || $_SERVER['HTTP_X_AUTH_KEY'] !== AUTH_KEY) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }
    
    // Создание подключения
    $conn = getBdConnection();
    $insert_sql = "UPDATE orders SET done = true WHERE id = $orderId";

    if ($conn->query($insert_sql) === TRUE) {
        // Проверяем, затронута ли хотя бы одна строка
        if ($conn->affected_rows > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Order updated successfully']);
        } else {
            // Если ни одна строка не затронута, возвращаем 404
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } else {
        // Если произошла ошибка в запросе
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
}

function getOrder($orderId) {
    
    $conn = getBdConnection();
    
    $selectById = "SELECT * FROM orders WHERE id = '$orderId'";
    $result = $conn->query($selectById);
    $items = [];
    $done = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $order = [
            'id' =>  $row["id"],
            "items" => $row["items"],
            "done" => $row["done"]
        ];
        echo json_encode($order);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        die();
    }

    http_response_code(200);
}

function listOrders() {
    if (!isset($_SERVER['HTTP_X_AUTH_KEY']) || $_SERVER['HTTP_X_AUTH_KEY'] !== AUTH_KEY) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    $doneFilter = isset($_GET['done']) ? (bool) $_GET['done'] : null;
    
    // Создание подключения
    $conn = getBdConnection();
    
    if (!is_null($doneFilter)) {
        $selectById = "SELECT * FROM orders WHERE done = '$doneFilter'";
    } else {
        $selectById = "SELECT * FROM orders";
    }
    $result = $conn->query($selectById);

    if ($result->num_rows > 0) {
        $orders = [];
    
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => $row["id"],
                'items' => $row["items"],
                'done' => $row["done"],
            ];
        }
    
        // Возвращаем массив записей в формате JSON
        echo json_encode($orders);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No orders found']);
    }
        
    http_response_code(200);
}

function getBdConnection() {
    $host = "mysql-db"; // Адрес сервера базы данных
    $username = "root"; // Имя пользователя
    $password = "root"; // Пароль
    $dbname = "pizza"; // Имя базы данных

    $conn = new mysqli($host, $username, $password, $dbname);

    return $conn;
}
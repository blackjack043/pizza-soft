<?php
class DataBase {
    private $conn;

    private function connect() {
        $host = getenv('DB_HOST') ?: 'mysql-db';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: 'root';
        $dbname = getenv('DB_NAME') ?: 'pizza';
    
        $conn = new mysqli($host, $username, $password, $dbname);
    
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
    
        return $conn;
    }
    
    public function __construct() {
        $this->conn = $this->connect();
    }

    public function getBdConnection() {
        return $this->conn;
    }

    public function getOrder($orderId) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return [
                'id' => $row["id"],
                'items' => json_decode($row["items"], true),
                'done' => (bool) $row["done"]
            ];
        }

        return null;
    }

    public function getOrders($doneFilter = null) {
        $query = "SELECT * FROM orders";
        if (!is_null($doneFilter)) {
            $query .= " WHERE done = ?";
        }

        $stmt = $this->conn->prepare($query);

        if (!is_null($doneFilter)) {
            $stmt->bind_param("i", $doneFilter);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];

        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => $row["id"],
                'items' => json_decode($row["items"], true),
                'done' => (bool) $row["done"]
            ];
        }

        return $orders;
    }

    public function createOrder($items) {
        $stmt = $this->conn->prepare("INSERT INTO orders (items, done) VALUES (?, 0)");
        $itemsJson = json_encode($items);
        $stmt->bind_param("s", $itemsJson);

        if (!$stmt->execute()) {
            throw new Exception("Failed to create order: " . $stmt->error);
        }

        return $this->conn->insert_id;
    }

    public function updateOrderItems($orderId, $newItems) {
        $stmt = $this->conn->prepare("UPDATE orders SET items = ? WHERE id = ?");
        $itemsJson = json_encode($newItems);
        $stmt->bind_param("si", $itemsJson, $orderId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update order items");
        }

        return true;
    }

    public function markOrderAsDone($orderId) {
        $stmt = $this->conn->prepare("UPDATE orders SET done = true WHERE id = ?");
        $stmt->bind_param("i", $orderId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to mark order as done");
        }

        return $stmt->affected_rows > 0;
    }
}

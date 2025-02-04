<?php
require 'BD.php';

class OrderService {
    private $db;

    public function __construct() {
        $this->db = new DataBase();
    }

    public function createOrder($data) {
        try {
            $orderId = $this->db->createOrder($data['items']);
            
        } catch (Exception $ex) {
            throw new Exception("Failed to create order: " . $ex->getMessage());
        }

        return [
            'order_id' => $orderId,
            'items' => $data['items'],
            'done' => false
        ];
    }

    public function getOrder($orderId) {
        try {
            $order = $this->db->getOrder($orderId);
        } catch (Exception $ex) {
            throw new Exception("Failed to get order: " . $ex->getMessage());
        }

        return $order;
    }

    public function getOrders() {
        $doneFilter = isset($_GET['done']) ? (bool) $_GET['done'] : null;
        try {
             $orders = $this->db->getOrders($doneFilter);
        } catch (Exception $ex) {
            throw new Exception("Failed to get orders: " . $ex->getMessage());
        }

        return $orders;
    }

    public function addItemsToOrder($orderId, $data) {
        $conn = $this->db->getBdConnection();
        $conn->begin_transaction(); // Начало транзакции
    
        try {
            // Блокируем строку заказа, чтобы другие запросы не изменяли её одновременно
            $stmt = $conn->prepare("SELECT items, done FROM orders WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 0) {
                throw new Exception('Order not found');
            }
    
            $order = $result->fetch_assoc();
    
            if ($order["done"]) {
                throw new Exception('Order already done');
            }
    
            // Объединяем существующие товары с новыми
            $existingItems = json_decode($order['items'], true) ?? [];
            $updatedItems = array_merge($existingItems, $data["items"]);
            $updatedItemsJson = json_encode($updatedItems);
    
            // Обновляем заказ
            $updateStmt = $conn->prepare("UPDATE orders SET items = ? WHERE id = ?");
            $updateStmt->bind_param("si", $updatedItemsJson, $orderId);
    
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update order');
            }
    
            $conn->commit(); // Подтверждаем изменения
    
        } catch (Exception $ex) {
            $conn->rollback(); // Откат изменений в случае ошибки
            throw new Exception("Failed to add items: " . $ex->getMessage());
        }

        return [
            "order_id" => $orderId,
            "items" => $updatedItems,
            "done" => false,
        ];
    }
    

    public function markOrderAsDone($orderId) {
        try {
            $order = $this->db->getOrder($orderId);
            if (!$order) {
                return false;
            }

            if ($order["done"]) {
                throw new Exception('Order already done');
            }

            $success = $this->db->markOrderAsDone($orderId);
            if (!$success) {
                throw new Exception('Order not updated');
            } 
        } catch (Exception $ex) {
            throw new Exception("Failed to mark order as done: " . $ex->getMessage());
        }

        return true;
    }
}

<?php

//require 'BD.php';
require 'HttpResponse.php';
require 'HttpRequest.php';
require 'Validation.php';
require 'OrderService.php';
require 'Manager.php';

class OrderController {
    // Внедрение зависимости через конструктор
    public function __construct() {
        $this->HttpResponse = new HttpResponse();
        $this->HttpRequest = new HttpRequest();
        $this->validate = new Validate();
        $this->manager = new Manager();
        $this->orderService = new OrderService();
    }
    

    function createOrder() {
        $data = $this->HttpRequest->getJsonBody();

        if (empty($data['items']) || !is_array($data['items']) || !$this->validate->validateIncomeData($data)) {
            $this->HttpResponse->sendJson(['error' => 'Invalid items'], 400);
        }

        try {
            $result = $this->orderService->createOrder($data);
        } catch (Exception $ex) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => 'Failed to create order',
                    'details' => $ex->getMessage()
                 ] , 400);
        } catch (Throwable $e) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $e->getMessage(),
                 ] , 500);
        };;

        $this->HttpResponse->sendJson($result, 201);
    }

    function addItemsToOrder($orderId) {
        $data = $this->HttpRequest->getJsonBody();
        
        if (empty($data['items']) || !is_array($data['items']) || !$this->validate->validateIncomeData($data)) {
            $this->HttpResponse->sendJson(['error' => 'Invalid items'], 400);
        }

        try {
            $result = $this->orderService->addItemsToOrder($orderId, $data);
        } catch (Exception $ex) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $ex->getMessage(),
                 ] , 400);
        } catch (Throwable $e) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $e->getMessage(),
                 ] , 500);
        };

        $this->HttpResponse->sendJson($result, 200);
        
    }

    function markOrderAsDone($orderId) {
        if (!$this->manager->checkAuthKey()) {
            $this->HttpResponse->sendJson(['error' => 'Forbidden'], 403);
        };

        try {
            $res = $this->orderService->markOrderAsDone($orderId);
        } catch (Exception $ex) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $ex->getMessage(),
                 ] , 400);
        } catch (Throwable $e) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $e->getMessage(),
                 ] , 500);
        };

        $this->HttpResponse->sendJson($res ? ['message' => 'Order make done'] :['message' => 'Order not found']  , $res ? 200 : 404);
    }

    function getOrder($orderId) {

        try {
            $order = $this->orderService->getOrder($orderId);
        } catch (Exception $ex) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $ex->getMessage(),
                 ] , 400);
        } catch (Throwable $e) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $e->getMessage(),
                 ] , 500);
        };;

        $this->HttpResponse->sendJson($order ? $order :['message' => 'Order not found']  , $order ? 200 : 404);
        
    }

    function listOrders() {
        if (!$this->manager->checkAuthKey()) {
            $this->HttpResponse->sendJson(['error' => 'Forbidden'], 403);
        };

        try {
            $orders = $this->orderService->getOrders();
        } catch (Exception $ex) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => 'No orders found',
                 ] , 400);
        } catch (Throwable $e) {
            $this->HttpResponse->sendJson(
                [   
                    'error' => $e->getMessage(),
                 ] , 500);
        };;

        $this->HttpResponse->sendJson($orders, 200);
    }
    
}
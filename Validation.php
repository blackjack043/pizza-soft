<?php

class Validate {
    function validateIncomeData($data) {

        $items = $data['items'] ?? [];
        $filteredItems = array_filter($items, function($item) {
            return is_string($item);
        });

        return $filteredItems === $items;
    }
}
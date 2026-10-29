<?php

declare(strict_types=1);

require_once __DIR__ . '/product_validator.php';

$validator = new ProductValidator();

$validCategoryIds = [1, 2, 3];

/**
 * Test 1: Valid product input
 */
$validInput = [
    'productName' => 'Fresh Milk',
    'categoryId' => '1',
    'description' => 'Fresh dairy milk',
    'price' => '8.50',
    'stockQuantity' => '20',
];

$errors = $validator->validateProductInput($validInput, $validCategoryIds);

echo "Test 1 - Valid product input: ";
echo $errors === [] ? "PASS\n" : "FAIL\n";

/**
 * Test 2: Empty product name
 */
$emptyNameInput = [
    'productName' => '',
    'categoryId' => '1',
    'description' => 'Fresh dairy milk',
    'price' => '8.50',
    'stockQuantity' => '20',
];

$errors = $validator->validateProductInput($emptyNameInput, $validCategoryIds);

echo "Test 2 - Empty product name: ";
echo isset($errors['productName']) ? "PASS\n" : "FAIL\n";

/**
 * Test 3: Negative price
 */
$negativePriceInput = [
    'productName' => 'Fresh Milk',
    'categoryId' => '1',
    'description' => 'Fresh dairy milk',
    'price' => '-5.00',
    'stockQuantity' => '20',
];

$errors = $validator->validateProductInput($negativePriceInput, $validCategoryIds);

echo "Test 3 - Negative price: ";
echo isset($errors['price']) ? "PASS\n" : "FAIL\n";

/**
 * Test 4: Negative stock quantity
 */
$negativeStockInput = [
    'productName' => 'Fresh Milk',
    'categoryId' => '1',
    'description' => 'Fresh dairy milk',
    'price' => '8.50',
    'stockQuantity' => '-10',
];

$errors = $validator->validateProductInput($negativeStockInput, $validCategoryIds);

echo "Test 4 - Negative stock quantity: ";
echo isset($errors['stockQuantity']) ? "PASS\n" : "FAIL\n";

/**
 * Test 5: Zero stock is valid
 */
$zeroStockInput = [
    'productName' => 'Fresh Milk',
    'categoryId' => '1',
    'description' => 'Fresh dairy milk',
    'price' => '8.50',
    'stockQuantity' => '0',
];

$errors = $validator->validateProductInput($zeroStockInput, $validCategoryIds);

echo "Test 5 - Zero stock quantity: ";
echo !isset($errors['stockQuantity']) ? "PASS\n" : "FAIL\n";

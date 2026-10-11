<?php

declare(strict_types=1);

/**
 * File:    product_service_test.php
 * Purpose: Check the Product CRUD and search functions against a test database.
 */

const VALID_PRODUCT_DATA = [
    'categoryId' => 2,
    'productName' => 'Roti Canai (Frozen, 5 pcs)',
    'description' => 'Ready-to-cook frozen roti canai.',
    'price' => 6.5,
    'stockQuantity' => 14,
];

function testGetAllProductsReturnsSampleData(): void
{
    $productService = new ProductService(createTestConnection());
    $products = $productService->getAllProducts();

    assertSame(10, count($products), 'Sample data should contain 10 products');
    assertSame('Fresh Produce', $products[0]->getCategoryName(), 'Category name should be joined');
}

function testCreateProductSavesEveryAttribute(): void
{
    $productService = new ProductService(createTestConnection());
    $newProductId = $productService->createProduct(VALID_PRODUCT_DATA);
    $savedProduct = $productService->findProductById($newProductId);

    assertTrue($savedProduct !== null, 'New product should be found by its ID');
    assertSame('Roti Canai (Frozen, 5 pcs)', $savedProduct->getProductName(), 'Name should be saved');
    assertSame(6.5, $savedProduct->getPrice(), 'Price should be saved as a float');
    assertSame(14, $savedProduct->getStockQuantity(), 'Stock should be saved as an int');
    assertSame(11, $productService->countProducts(), 'Product count should increase by one');
}

function testSearchProductByKeywordMatchesNameAndDescription(): void
{
    $productService = new ProductService(createTestConnection());

    $nameMatches = $productService->searchProduct(['keyword' => 'eggs']);
    $descriptionMatches = $productService->searchProduct(['keyword' => 'Ipoh']);

    assertSame(1, count($nameMatches), 'Keyword "eggs" should match one product name');
    assertSame('Kopi O Powder 500g', $descriptionMatches[0]->getProductName(), 'Keyword should also search descriptions');
}

function testSearchProductCombinesCategoryAndPriceRange(): void
{
    $productService = new ProductService(createTestConnection());
    $matches = $productService->searchProduct(['categoryId' => 1, 'minPrice' => 3.0, 'maxPrice' => 10.0]);
    $matchedNames = array_map(static fn (Product $product) => $product->getProductName(), $matches);

    assertSame(['Cameron Highlands Tomatoes 500g', 'Kampung Eggs (Tray of 10)'], $matchedNames, 'All criteria should apply together');
}

function testSearchProductReturnsEmptyArrayWhenNotFound(): void
{
    $productService = new ProductService(createTestConnection());

    assertSame([], $productService->searchProduct(['keyword' => 'durian']), 'Unknown keyword should return no products');
    assertSame([], $productService->searchProduct(['productId' => 999]), 'Unknown ID should return no products');
}

function testUpdateProductSavesNewValues(): void
{
    $productService = new ProductService(createTestConnection());
    $product = $productService->findProductById(1);
    $product->updateProduct(['categoryId' => 1, 'productName' => 'Kampung Eggs (Tray of 30)',
        'description' => 'Bigger tray.', 'price' => 21.0, 'stockQuantity' => 8]);

    assertTrue($productService->updateProduct($product), 'Update should report success');

    $reloadedProduct = $productService->findProductById(1);
    assertSame('Kampung Eggs (Tray of 30)', $reloadedProduct->getProductName(), 'New name should be saved');
    assertSame(21.0, $reloadedProduct->getPrice(), 'New price should be saved');
}

function testDeleteProductRemovesOnlyThatProduct(): void
{
    $productService = new ProductService(createTestConnection());

    assertTrue($productService->deleteProduct(3), 'Deleting an existing product should succeed');
    assertSame(null, $productService->findProductById(3), 'Deleted product should no longer be found');
    assertSame(9, $productService->countProducts(), 'Only one product should be removed');
    assertTrue(!$productService->deleteProduct(3), 'Deleting the same product again should report false');
}

function testCheckStockAndStockStatus(): void
{
    $productService = new ProductService(createTestConnection());

    assertSame('Low stock', $productService->findProductById(3)->getStockStatus(), '3 left is low stock');
    assertSame('Out of stock', $productService->findProductById(9)->getStockStatus(), '0 left is out of stock');
    assertTrue($productService->findProductById(1)->checkStock(40), 'Exactly the stock amount is allowed');
    assertTrue(!$productService->findProductById(1)->checkStock(41), 'More than the stock amount is refused');
}

function testProductCrudWorksTogether(): void
{
    $productService = new ProductService(createTestConnection());
    $productValidator = new ProductValidator();
    $formInput = ['productName' => 'Teh Tarik Mix', 'categoryId' => '3', 'description' => '3-in-1 sachets.',
        'price' => '8.90', 'stockQuantity' => '50'];

    // Create: valid form input is saved and the full list shows it
    assertSame([], $productValidator->validateProductInput($formInput, [1, 2, 3, 4, 5]), 'Form input should be valid');
    $newProductId = $productService->createProduct($productValidator->toProductData($formInput));
    assertSame(11, count($productService->getAllProducts()), 'List should show the new product');

    // Search: the new product can be found by keyword
    $searchResults = $productService->searchProduct($productValidator->toSearchCriteria(['keyword' => 'teh tarik']));
    assertSame($newProductId, $searchResults[0]->getProductId(), 'Search should find the new product');

    // Edit: the saved change is what the list shows afterwards
    $product = $productService->findProductById($newProductId);
    $product->updateProduct(['categoryId' => 3, 'productName' => 'Teh Tarik Mix (Big Pack)',
        'description' => '3-in-1 sachets.', 'price' => 15.5, 'stockQuantity' => 20]);
    $productService->updateProduct($product);
    assertSame('Teh Tarik Mix (Big Pack)', $productService->findProductById($newProductId)->getProductName(), 'Edited name should be saved');

    // Delete: the product is gone from the list and from search
    assertTrue($productService->deleteProduct($newProductId), 'Delete should succeed');
    assertSame(10, count($productService->getAllProducts()), 'List should no longer contain the product');
    assertSame([], $productService->searchProduct(['productId' => $newProductId]), 'Search should now return Not found');
}

return [
    'testGetAllProductsReturnsSampleData',
    'testCreateProductSavesEveryAttribute',
    'testSearchProductByKeywordMatchesNameAndDescription',
    'testSearchProductCombinesCategoryAndPriceRange',
    'testSearchProductReturnsEmptyArrayWhenNotFound',
    'testUpdateProductSavesNewValues',
    'testDeleteProductRemovesOnlyThatProduct',
    'testCheckStockAndStockStatus',
    'testProductCrudWorksTogether',
];

<?php

declare(strict_types=1);

/**
 * File:    category_service_test.php
 * Purpose: Check Category create, search, update and delete rules.
 */

function testCategoriesIncludeProductCounts(): void
{
    $categoryService = new CategoryService(createTestConnection());
    $freshProduce = $categoryService->searchCategories(['keyword' => 'fresh'])[0];

    assertSame(5, count($categoryService->getAllCategories()), 'Sample data has 5 categories');
    assertSame(3, $freshProduce->getProductCount(), 'Fresh Produce has 3 sample products');
    assertSame([], $categoryService->searchCategories(['keyword' => 'frozen']), 'Unknown keyword should return nothing');
}

function testCreateAndUpdateCategoryWithUniqueName(): void
{
    $categoryService = new CategoryService(createTestConnection());
    $categoryValidator = new CategoryValidator();
    $duplicateInput = ['categoryName' => 'bakery', 'description' => ''];

    $duplicateErrors = $categoryValidator->validateCategoryInput($duplicateInput, $categoryService->isCategoryNameTaken('bakery'));
    assertTrue(isset($duplicateErrors['categoryName']), 'Duplicate name (any case) should be refused');

    $newCategoryId = $categoryService->createCategory(['categoryName' => 'Frozen Food', 'description' => 'Frozen items']);
    $category = $categoryService->findCategoryById($newCategoryId);
    $category->setCategoryName('Frozen & Chilled');

    assertTrue($categoryService->updateCategory($category), 'Update should succeed');
    assertSame('Frozen & Chilled', $categoryService->findCategoryById($newCategoryId)->getCategoryName(), 'New name should be saved');
    assertTrue(!$categoryService->isCategoryNameTaken('Frozen & Chilled', $newCategoryId), 'A category may keep its own name');
}

function testDeleteCategoryOnlyWhenEmpty(): void
{
    $categoryService = new CategoryService(createTestConnection());
    $newCategoryId = $categoryService->createCategory(['categoryName' => 'Seasonal', 'description' => '']);

    assertThrows(fn () => $categoryService->deleteCategory(1), 'still has 3 product', 'A category with products must not be deleted');
    assertTrue($categoryService->deleteCategory($newCategoryId), 'An empty category can be deleted');
    assertSame(null, $categoryService->findCategoryById($newCategoryId), 'Deleted category should be gone');
}

function testCategoryWithDeletedProductsExplainsDeleteHistory(): void
{
    $connection = createTestConnection();
    $categoryService = new CategoryService($connection);
    $productService = new ProductService($connection);
    $productService->deleteProduct(10); // the only Household product

    assertSame(0, $categoryService->findCategoryById(5)->getProductCount(), 'Deleted products are not counted in the category');
    assertThrows(fn () => $categoryService->deleteCategory(5), 'in Delete History', 'Category must not be deleted while its products can still be restored');

    $productService->permanentlyDeleteProduct(10);
    assertTrue($categoryService->deleteCategory(5), 'Category can be deleted once Delete History no longer uses it');
}

return [
    'testCategoriesIncludeProductCounts',
    'testCreateAndUpdateCategoryWithUniqueName',
    'testDeleteCategoryOnlyWhenEmpty',
    'testCategoryWithDeletedProductsExplainsDeleteHistory',
];

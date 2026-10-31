<?php

declare(strict_types=1);

/**
 * File:    code_generator_test.php
 * Purpose: Check the readable category codes (FRP) and product codes (FRP-001).
 */

function testCategoryCodesComeFromTheName(): void
{
    $codeGenerator = new CodeGenerator();

    assertSame('FRP', $codeGenerator->makeCategoryCode('Fresh Produce', []), 'Two words: 2 letters + 1 letter');
    assertSame('BAK', $codeGenerator->makeCategoryCode('Bakery', []), 'One word: first 3 letters');
    assertSame('FRC', $codeGenerator->makeCategoryCode('Frozen & Chilled', []), 'Symbols are ignored');
    assertSame('TEA', $codeGenerator->makeCategoryCode('Tea', []), 'A 3-letter name is its own code');
    assertSame('CAT', $codeGenerator->makeCategoryCode('2026', []), 'A name without letters still gets a code');
}

function testCategoryCodeGetsNumberWhenTaken(): void
{
    $codeGenerator = new CodeGenerator();

    assertSame('FRF2', $codeGenerator->makeCategoryCode('Frozen Food', ['FRF']), 'Second category with the same letters');
    assertSame('FRF3', $codeGenerator->makeCategoryCode('Frozen Fish', ['frf', 'FRF2']), 'Taken codes are compared without case');
}

function testProductCodesCountInsideTheCategory(): void
{
    $codeGenerator = new CodeGenerator();

    assertSame('FRP-001', $codeGenerator->makeProductCode('FRP', 0), 'First product in a category');
    assertSame('FRP-012', $codeGenerator->makeProductCode('FRP', 11), 'Numbers are padded to 3 digits');
    assertSame(12, $codeGenerator->readProductNumber('FRP-012'), 'The running number can be read back');
}

function testNewCategoryAndProductGetCodesAutomatically(): void
{
    $connection = createTestConnection();
    $categoryService = new CategoryService($connection);
    $productService = new ProductService($connection);

    $frozenId = $categoryService->createCategory(['categoryName' => 'Frozen Food', 'description' => '']);
    $frozenFishId = $categoryService->createCategory(['categoryName' => 'Frozen Fish', 'description' => '']);
    assertSame('FRF', $categoryService->findCategoryById($frozenId)->getCategoryCode(), 'New category gets a code from its name');
    assertSame('FRF2', $categoryService->findCategoryById($frozenFishId)->getCategoryCode(), 'A clashing code gets a number');

    $eggsTwoId = $productService->createProduct(['categoryId' => 1, 'productName' => 'Duck Eggs (Tray of 10)',
        'description' => 'Salted duck eggs.', 'price' => 9.9, 'stockQuantity' => 10]);
    assertSame('FRP-004', $productService->findProductById($eggsTwoId)->getProductCode(), 'Fresh Produce already has FRP-001 to FRP-003');

    $frozenProductId = $productService->createProduct(['categoryId' => $frozenId, 'productName' => 'Frozen Peas 500g',
        'description' => 'Green peas.', 'price' => 4.5, 'stockQuantity' => 20]);
    assertSame('FRF-001', $productService->findProductById($frozenProductId)->getProductCode(), 'First product in a new category');
}

function testProductCodeChangesOnlyWhenTheCategoryChanges(): void
{
    $productService = new ProductService(createTestConnection());
    $tomatoes = $productService->findProductById(3);

    $tomatoes->setPrice(4.5);
    $productService->updateProduct($tomatoes);
    assertSame('FRP-003', $productService->findProductById(3)->getProductCode(), 'Editing other fields keeps the code');

    $tomatoes->setCategoryId(4);
    $productService->updateProduct($tomatoes);
    assertSame('PAN-003', $productService->findProductById(3)->getProductCode(), 'Moving to Pantry gives the next Pantry code');
}

function testDeletedProductCodesAreNotReused(): void
{
    $productService = new ProductService(createTestConnection());
    $productService->deleteProduct(10); // HOU-001 goes to Delete History

    $newProductId = $productService->createProduct(['categoryId' => 5, 'productName' => 'Floor Cleaner 1L',
        'description' => 'Pine scent.', 'price' => 7.2, 'stockQuantity' => 8]);
    assertSame('HOU-002', $productService->findProductById($newProductId)->getProductCode(), 'HOU-001 stays reserved for the deleted product');

    $productService->restoreProduct(10);
    assertSame('HOU-001', $productService->findProductById(10)->getProductCode(), 'The restored product keeps its own code');
}

function testSearchFindsProductsAndCategoriesByCode(): void
{
    $connection = createTestConnection();

    assertSame('Kopi O Powder 500g', (new ProductService($connection))->searchProduct(['keyword' => 'BEV-001'])[0]->getProductName(), 'Product search matches the code');
    assertSame('Pantry', (new CategoryService($connection))->searchCategories(['keyword' => 'PAN'])[0]->getCategoryName(), 'Category search matches the code');
}

return [
    'testCategoryCodesComeFromTheName',
    'testCategoryCodeGetsNumberWhenTaken',
    'testProductCodesCountInsideTheCategory',
    'testNewCategoryAndProductGetCodesAutomatically',
    'testProductCodeChangesOnlyWhenTheCategoryChanges',
    'testDeletedProductCodesAreNotReused',
    'testSearchFindsProductsAndCategoriesByCode',
];

<?php

declare(strict_types=1);

/**
 * File:    product_validator_test.php
 * Purpose: Check the input rules for product forms and search criteria.
 */

const VALID_CATEGORY_IDS = [1, 2, 3, 4, 5];

function testValidProductInputHasNoErrors(): void
{
    $productValidator = new ProductValidator();
    $formInput = ['productName' => 'Teh Tarik Mix', 'categoryId' => '3', 'description' => '3-in-1 sachets.',
        'price' => '8.90', 'stockQuantity' => '50'];

    assertSame([], $productValidator->validateProductInput($formInput, VALID_CATEGORY_IDS), 'Valid input should pass');
    assertSame(8.9, $productValidator->toProductData($formInput)['price'], 'Price should become a float');
    assertSame(50, $productValidator->toProductData($formInput)['stockQuantity'], 'Stock should become an int');
}

function testEmptyProductInputReportsEveryRequiredField(): void
{
    $productValidator = new ProductValidator();
    $validationErrors = $productValidator->validateProductInput([], VALID_CATEGORY_IDS);

    assertSame(
        ['productName', 'categoryId', 'description', 'price', 'stockQuantity'],
        array_keys($validationErrors),
        'Every required field should have an error'
    );
}

function testInvalidNumbersAndUnknownCategoryAreRejected(): void
{
    $productValidator = new ProductValidator();
    $formInput = ['productName' => 'Test', 'categoryId' => '99', 'description' => 'x',
        'price' => '-1', 'stockQuantity' => '2.5'];
    $validationErrors = $productValidator->validateProductInput($formInput, VALID_CATEGORY_IDS);

    assertTrue(isset($validationErrors['categoryId']), 'Unknown category should be rejected');
    assertTrue(isset($validationErrors['price']), 'Negative price should be rejected');
    assertTrue(isset($validationErrors['stockQuantity']), 'Decimal stock should be rejected');
}

function testProductNameLongerThanLimitIsRejected(): void
{
    $productValidator = new ProductValidator();
    $formInput = ['productName' => str_repeat('a', ProductValidator::MAX_PRODUCT_NAME_LENGTH + 1),
        'categoryId' => '1', 'description' => 'x', 'price' => '1.00', 'stockQuantity' => '1'];

    assertTrue(
        isset($productValidator->validateProductInput($formInput, VALID_CATEGORY_IDS)['productName']),
        'Name over the limit should be rejected'
    );
}

function testSearchInputRejectsReversedPriceRange(): void
{
    $productValidator = new ProductValidator();
    $searchErrors = $productValidator->validateSearchInput(['minPrice' => '20', 'maxPrice' => '5']);

    assertTrue(isset($searchErrors['maxPrice']), 'Max price below min price should be rejected');
}

function testEmptySearchMeansNoCriteria(): void
{
    $productValidator = new ProductValidator();
    $searchCriteria = $productValidator->toSearchCriteria(['productId' => '', 'keyword' => '  ']);

    assertTrue(!$productValidator->hasSearchCriteria($searchCriteria), 'Blank fields should not count as a search');
    assertTrue(
        $productValidator->hasSearchCriteria($productValidator->toSearchCriteria(['productId' => '4'])),
        'A product ID alone should count as a search'
    );
}

function testPriceWithMoreThanTwoDecimalsHasClearMessage(): void
{
    $productValidator = new ProductValidator();
    $formInput = ['productName' => 'Test', 'categoryId' => '1', 'description' => 'x', 'stockQuantity' => '1'];
    $priceError = static fn (string $price): string =>
        $productValidator->validateProductInput(['price' => $price] + $formInput, VALID_CATEGORY_IDS)['price'] ?? '';

    assertTrue(str_contains($priceError('0.001'), 'maximum of two decimal places'), '0.001 must explain the two-decimal rule');
    assertTrue(str_contains($priceError('4.999'), 'maximum of two decimal places'), '4.999 must explain the two-decimal rule');
    assertTrue(str_contains($priceError('abc'), 'positive number'), 'Text must be explained as not a number');
    assertTrue(str_contains($priceError(''), 'required'), 'Empty price must say it is required');
    assertTrue(str_contains($priceError('0.00'), 'more than 0'), 'Zero price must be refused');
    assertSame('', $priceError('4.90'), 'A valid price has no error');
}

return [
    'testPriceWithMoreThanTwoDecimalsHasClearMessage',
    'testValidProductInputHasNoErrors',
    'testEmptyProductInputReportsEveryRequiredField',
    'testInvalidNumbersAndUnknownCategoryAreRejected',
    'testProductNameLongerThanLimitIsRejected',
    'testSearchInputRejectsReversedPriceRange',
    'testEmptySearchMeansNoCriteria',
];

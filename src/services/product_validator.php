<?php

declare(strict_types=1);

/**
 * Class Name:  ProductValidator
 * Purpose:     Check and convert user input for the Product module.
 * Description: Form values always arrive as strings. This class checks them
 *              against the product rules and converts valid values into the
 *              correct data types (int, float, string) before they reach
 *              ProductService. Validation is done on the server even though
 *              the browser also checks the form, because browser checks can
 *              be bypassed.
 */
class ProductValidator
{
    public const MAX_PRODUCT_NAME_LENGTH = 100;
    public const MAX_DESCRIPTION_LENGTH = 1000;
    public const MAX_PRICE = 99999.99;
    public const MAX_STOCK_QUANTITY = 100000;

    private const PRICE_PATTERN = '/^\d+(\.\d{1,2})?$/';
    private const WHOLE_NUMBER_PATTERN = '/^\d+$/';

    /**
     * Function/Method Name: validateProductInput
     * Purpose:      Check the create/edit product form.
     * Parameters:   array $formInput - raw $_POST values
     *               int[] $validCategoryIds - IDs of categories that exist
     * Return Value: array - field name => error message (empty array when valid)
     * Description:  Each field is checked by its own small method so the rules are
     *               easy to read and change.
     */
    public function validateProductInput(array $formInput, array $validCategoryIds): array
    {
        $errors = [
            'productName' => $this->validateProductName((string) ($formInput['productName'] ?? '')),
            'categoryId' => $this->validateCategoryId((string) ($formInput['categoryId'] ?? ''), $validCategoryIds),
            'description' => $this->validateDescription((string) ($formInput['description'] ?? '')),
            'price' => $this->validatePrice((string) ($formInput['price'] ?? '')),
            'stockQuantity' => $this->validateStockQuantity((string) ($formInput['stockQuantity'] ?? '')),
        ];

        return array_filter($errors, static fn (?string $errorMessage) => $errorMessage !== null);
    }

    /**
     * Function/Method Name: toProductData
     * Purpose:      Convert a valid product form into typed values.
     * Parameters:   array $formInput - raw $_POST values that passed validateProductInput()
     * Return Value: array - categoryId (int), productName (string), description (string),
     *               price (float), stockQuantity (int)
     * Description:  Trims text so stray spaces are not saved.
     */
    public function toProductData(array $formInput): array
    {
        return [
            'categoryId' => (int) $formInput['categoryId'],
            'productName' => trim((string) $formInput['productName']),
            'description' => trim((string) $formInput['description']),
            'price' => round((float) $formInput['price'], 2),
            'stockQuantity' => (int) $formInput['stockQuantity'],
        ];
    }

    /**
     * Function/Method Name: validateSearchInput
     * Purpose:      Check the search form criteria.
     * Parameters:   array $queryInput - raw $_GET values
     * Return Value: array - field name => error message (empty array when valid)
     * Description:  Every criterion is optional, but the ones given must be well formed
     *               and the price range must not be reversed.
     */
    public function validateSearchInput(array $queryInput): array
    {
        $errors = [];
        $productIdText = trim((string) ($queryInput['productId'] ?? ''));
        $minPriceText = trim((string) ($queryInput['minPrice'] ?? ''));
        $maxPriceText = trim((string) ($queryInput['maxPrice'] ?? ''));

        if ($productIdText !== '' && !preg_match(self::WHOLE_NUMBER_PATTERN, $productIdText)) {
            $errors['productId'] = 'Product ID must be a whole number, for example 3.';
        }

        if ($minPriceText !== '' && !preg_match(self::PRICE_PATTERN, $minPriceText)) {
            $errors['minPrice'] = 'Minimum price must be a number such as 2.50.';
        }

        if ($maxPriceText !== '' && !preg_match(self::PRICE_PATTERN, $maxPriceText)) {
            $errors['maxPrice'] = 'Maximum price must be a number such as 20.00.';
        }

        $isPriceRangeGiven = $minPriceText !== '' && $maxPriceText !== '' && $errors === [];
        if ($isPriceRangeGiven && (float) $minPriceText > (float) $maxPriceText) {
            $errors['maxPrice'] = 'Maximum price must be greater than or equal to minimum price.';
        }

        return $errors;
    }

    /**
     * Function/Method Name: toSearchCriteria
     * Purpose:      Convert a valid search form into typed criteria for ProductService.
     * Parameters:   array $queryInput - raw $_GET values that passed validateSearchInput()
     * Return Value: array - productId, keyword, categoryId, minPrice, maxPrice
     *               (null or '' when the user left the field empty)
     * Description:  Empty fields become null so the service skips them.
     */
    public function toSearchCriteria(array $queryInput): array
    {
        return [
            'productId' => $this->toNullableInt($queryInput['productId'] ?? ''),
            'keyword' => trim((string) ($queryInput['keyword'] ?? '')),
            'categoryId' => $this->toNullableInt($queryInput['categoryId'] ?? ''),
            'minPrice' => $this->toNullableFloat($queryInput['minPrice'] ?? ''),
            'maxPrice' => $this->toNullableFloat($queryInput['maxPrice'] ?? ''),
        ];
    }

    /**
     * Function/Method Name: hasSearchCriteria
     * Purpose:      Tell whether the user typed or chose at least one criterion.
     * Parameters:   array $searchCriteria - output of toSearchCriteria()
     * Return Value: bool - true when at least one criterion is filled in
     * Description:  Lets the controller show the empty form instead of "Not found"
     *               when the page is first opened.
     */
    public function hasSearchCriteria(array $searchCriteria): bool
    {
        foreach ($searchCriteria as $criterionValue) {
            if ($criterionValue !== null && $criterionValue !== '') {
                return true;
            }
        }

        return false;
    }

    private function validateProductName(string $productName): ?string
    {
        $productName = trim($productName);
        if ($productName === '') {
            return 'Product name is required.';
        }

        if (mb_strlen($productName) > self::MAX_PRODUCT_NAME_LENGTH) {
            return 'Product name must be ' . self::MAX_PRODUCT_NAME_LENGTH . ' characters or fewer.';
        }

        return null;
    }

    private function validateCategoryId(string $categoryIdText, array $validCategoryIds): ?string
    {
        $isKnownCategory = preg_match(self::WHOLE_NUMBER_PATTERN, $categoryIdText)
            && in_array((int) $categoryIdText, $validCategoryIds, true);

        return $isKnownCategory ? null : 'Please choose a category from the list.';
    }

    private function validateDescription(string $description): ?string
    {
        $description = trim($description);
        if ($description === '') {
            return 'Description is required.';
        }

        if (mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            return 'Description must be ' . self::MAX_DESCRIPTION_LENGTH . ' characters or fewer.';
        }

        return null;
    }

    private function validatePrice(string $priceText): ?string
    {
        $priceText = trim($priceText);
        if (!preg_match(self::PRICE_PATTERN, $priceText)) {
            return 'Price must be a number with up to 2 decimal places, for example 4.90.';
        }

        $price = (float) $priceText;
        if ($price <= 0 || $price > self::MAX_PRICE) {
            return 'Price must be more than 0 and at most ' . number_format(self::MAX_PRICE, 2) . '.';
        }

        return null;
    }

    private function validateStockQuantity(string $stockQuantityText): ?string
    {
        $stockQuantityText = trim($stockQuantityText);
        if (!preg_match(self::WHOLE_NUMBER_PATTERN, $stockQuantityText)) {
            return 'Stock quantity must be a whole number of 0 or more.';
        }

        if ((int) $stockQuantityText > self::MAX_STOCK_QUANTITY) {
            return 'Stock quantity must be at most ' . number_format(self::MAX_STOCK_QUANTITY) . '.';
        }

        return null;
    }

    private function toNullableInt(mixed $inputValue): ?int
    {
        $inputText = trim((string) $inputValue);

        return $inputText === '' ? null : (int) $inputText;
    }

    private function toNullableFloat(mixed $inputValue): ?float
    {
        $inputText = trim((string) $inputValue);

        return $inputText === '' ? null : (float) $inputText;
    }
}

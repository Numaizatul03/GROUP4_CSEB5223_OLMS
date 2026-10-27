<?php

declare(strict_types=1);

/**
 * Class Name:  CategoryValidator
 * Purpose:     Check and convert user input for the Category module.
 * Description: Same approach as ProductValidator: check the raw strings, then
 *              convert valid input into typed values.
 */
class CategoryValidator
{
    public const MAX_CATEGORY_NAME_LENGTH = 50;
    public const MAX_DESCRIPTION_LENGTH = 255;

    /**
     * Function/Method Name: validateCategoryInput
     * Purpose:      Check the create/edit category form.
     * Parameters:   array $formInput - raw $_POST values
     *               bool $isNameTaken - result of CategoryService::isCategoryNameTaken()
     * Return Value: array - field name => error message (empty array when valid)
     * Description:  Name is required, unique and at most 50 characters.
     */
    public function validateCategoryInput(array $formInput, bool $isNameTaken): array
    {
        $errors = [];
        $categoryName = trim((string) ($formInput['categoryName'] ?? ''));
        $description = trim((string) ($formInput['description'] ?? ''));

        if ($categoryName === '') {
            $errors['categoryName'] = 'Category name is required.';
        } elseif (mb_strlen($categoryName) > self::MAX_CATEGORY_NAME_LENGTH) {
            $errors['categoryName'] = 'Category name must be ' . self::MAX_CATEGORY_NAME_LENGTH . ' characters or fewer.';
        } elseif ($isNameTaken) {
            $errors['categoryName'] = 'A category called "' . $categoryName . '" already exists.';
        }

        if (mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            $errors['description'] = 'Description must be ' . self::MAX_DESCRIPTION_LENGTH . ' characters or fewer.';
        }

        return $errors;
    }

    public function toCategoryData(array $formInput): array
    {
        return [
            'categoryName' => trim((string) ($formInput['categoryName'] ?? '')),
            'description' => trim((string) ($formInput['description'] ?? '')),
        ];
    }

    /**
     * Function/Method Name: validateSearchInput
     * Purpose:      Check the Manage Categories search form.
     * Parameters:   array $queryInput - raw $_GET values
     * Return Value: array - field name => error message
     * Description:  Category ID, when given, must be a whole number.
     */
    public function validateSearchInput(array $queryInput): array
    {
        $categoryIdText = trim((string) ($queryInput['categoryId'] ?? ''));

        return $categoryIdText !== '' && !ctype_digit($categoryIdText)
            ? ['categoryId' => 'Category ID must be a whole number, for example 2.']
            : [];
    }

    public function toSearchCriteria(array $queryInput): array
    {
        $categoryIdText = trim((string) ($queryInput['categoryId'] ?? ''));

        return [
            'categoryId' => $categoryIdText === '' ? null : (int) $categoryIdText,
            'keyword' => trim((string) ($queryInput['keyword'] ?? '')),
        ];
    }
}

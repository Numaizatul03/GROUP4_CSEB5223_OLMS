<?php

declare(strict_types=1);

/**
 * Class Name:  CodeGenerator
 * Purpose:     Make readable, unique codes for categories and products.
 * Description: Category codes come from the category name (Fresh Produce -> FRP,
 *              Bakery -> BAK). Product codes are the category code plus a running
 *              number inside that category (FRP-001, FRP-002). The numeric IDs are
 *              kept as the database keys; codes are what people read and search.
 *              This class only works with strings, so it needs no database.
 */
class CodeGenerator
{
    public const CATEGORY_CODE_LENGTH = 3;
    public const PRODUCT_NUMBER_DIGITS = 3;
    private const FALLBACK_CATEGORY_CODE = 'CAT';

    /**
     * Function/Method Name: makeCategoryCode
     * Purpose:      Create a unique category code from a category name.
     * Parameters:   string $categoryName - e.g. "Fresh Produce"
     *               string[] $takenCodes - codes already used by other categories
     * Return Value: string - e.g. "FRP", or "FRP2" when "FRP" is already taken
     * Description:  One word: its first 3 letters (Bakery -> BAK). Two or more words:
     *               first 2 letters of the first word + first letter of the second
     *               (Fresh Produce -> FRP). Symbols and numbers are ignored.
     */
    public function makeCategoryCode(string $categoryName, array $takenCodes): string
    {
        $baseCode = $this->makeBaseCategoryCode($categoryName);
        $takenCodes = array_map('strtoupper', $takenCodes);

        $categoryCode = $baseCode;
        for ($suffix = 2; in_array($categoryCode, $takenCodes, true); $suffix++) {
            $categoryCode = $baseCode . $suffix;
        }

        return $categoryCode;
    }

    /**
     * Function/Method Name: makeProductCode
     * Purpose:      Create the next product code inside a category.
     * Parameters:   string $categoryCode - e.g. "FRP"
     *               int $highestNumberUsed - the largest number already used in that category (0 if none)
     * Return Value: string - e.g. "FRP-004"
     * Description:  Numbers are padded to 3 digits so codes sort in order.
     */
    public function makeProductCode(string $categoryCode, int $highestNumberUsed): string
    {
        return $categoryCode . '-' . str_pad((string) ($highestNumberUsed + 1), self::PRODUCT_NUMBER_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Function/Method Name: readProductNumber
     * Purpose:      Get the running number from a product code.
     * Parameters:   string $productCode - e.g. "FRP-012"
     * Return Value: int - e.g. 12, or 0 when the code has no number
     * Description:  Used to find the highest number already used in a category.
     */
    public function readProductNumber(string $productCode): int
    {
        return preg_match('/-(\d+)$/', $productCode, $matches) ? (int) $matches[1] : 0;
    }

    private function makeBaseCategoryCode(string $categoryName): string
    {
        $words = preg_split('/[^A-Za-z]+/', strtoupper($categoryName), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === []) {
            return self::FALLBACK_CATEGORY_CODE;
        }

        $baseCode = count($words) === 1
            ? substr($words[0], 0, self::CATEGORY_CODE_LENGTH)
            : substr($words[0], 0, 2) . substr($words[1], 0, 1);

        // Very short names (e.g. "Tea") still give a 3-letter code
        return str_pad($baseCode, self::CATEGORY_CODE_LENGTH, 'X');
    }
}

<?php

declare(strict_types=1);

/**
 * Class Name:  InputGuard
 * Purpose:     Read user input safely, whatever shape it arrives in.
 * Description: Form and URL values can be changed by anyone. A value that was
 *              expected to be text may arrive as a list (?keyword[]=x), be very
 *              long, or contain hidden control characters. These helpers always
 *              return clean values of the right type, so the rest of the code
 *              never has to deal with those cases.
 */
final class InputGuard
{
    public const MAX_TEXT_LENGTH = 5000;
    public const MAX_SEARCH_LENGTH = 100;
    public const MAX_ID_DIGITS = 10;
    public const LIKE_ESCAPE_CHARACTER = '!';

    /**
     * Function/Method Name: isSafeScalar
     * Purpose:      Check that one input value is plain text (not a list or object).
     * Parameters:   mixed $inputValue
     * Return Value: bool - true for strings and numbers, false for arrays, objects and null
     * Description:  Used by InputGuardMiddleware to reject tampered requests early.
     */
    public static function isSafeScalar(mixed $inputValue): bool
    {
        return is_string($inputValue) || is_int($inputValue) || is_float($inputValue);
    }

    /**
     * Function/Method Name: readText
     * Purpose:      Read one text field safely.
     * Parameters:   array $source - $_GET, $_POST or a decoded JSON body
     *               string $fieldName
     *               int $maxLength - longer text is cut to this length
     * Return Value: string - trimmed text without control characters ('' when missing or not text)
     * Description:  Removes invisible control characters (for example null bytes)
     *               but keeps normal line breaks and tabs.
     */
    public static function readText(array $source, string $fieldName, int $maxLength = self::MAX_TEXT_LENGTH): string
    {
        $inputValue = $source[$fieldName] ?? '';
        if (!self::isSafeScalar($inputValue)) {
            return '';
        }

        // Remove control characters first, then trim, so "eggs \x07" becomes "eggs" and not "eggs "
        $cleanText = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $inputValue) ?? '');

        return mb_substr($cleanText, 0, $maxLength);
    }

    /**
     * Function/Method Name: readId
     * Purpose:      Read a record ID such as productId.
     * Parameters:   array $source, string $fieldName
     * Return Value: ?int - the ID, or null when missing, not a whole number, 0, or too long
     * Description:  IDs longer than 10 digits are refused instead of overflowing.
     */
    public static function readId(array $source, string $fieldName): ?int
    {
        $idText = self::readText($source, $fieldName, self::MAX_ID_DIGITS + 1);
        if (!ctype_digit($idText) || strlen($idText) > self::MAX_ID_DIGITS || (int) $idText === 0) {
            return null;
        }

        return (int) $idText;
    }

    /**
     * Function/Method Name: validateKeywordLength
     * Purpose:      Refuse a search word that is too long to be a real search.
     * Parameters:   array $queryInput - raw $_GET values
     * Return Value: array - ['keyword' => message] when too long, otherwise []
     * Description:  Shared by every search form so they all use the same limit (100 characters).
     */
    public static function validateKeywordLength(array $queryInput): array
    {
        $keyword = self::readText($queryInput, 'keyword', self::MAX_TEXT_LENGTH);

        return mb_strlen($keyword) > self::MAX_SEARCH_LENGTH
            ? ['keyword' => 'Search words must be ' . self::MAX_SEARCH_LENGTH . ' characters or fewer.']
            : [];
    }

    /**
     * Function/Method Name: escapeLike
     * Purpose:      Make a search word match literally inside an SQL LIKE pattern.
     * Parameters:   string $searchText - what the user typed
     * Return Value: string - text where %, _ and ! are escaped with "!"
     * Description:  Without this, searching "%" would match every product and
     *               "_" would match any single character. Queries use ESCAPE '!'.
     */
    public static function escapeLike(string $searchText): string
    {
        $escape = self::LIKE_ESCAPE_CHARACTER;

        return str_replace([$escape, '%', '_'], [$escape . $escape, $escape . '%', $escape . '_'], $searchText);
    }

    /**
     * Function/Method Name: makeContainsPattern
     * Purpose:      Build a "contains" pattern for SQL LIKE from a search word.
     * Parameters:   string $searchText
     * Return Value: string - e.g. "%egg%" with special characters escaped
     * Description:  Shared by every search in the system.
     */
    public static function makeContainsPattern(string $searchText): string
    {
        return '%' . self::escapeLike($searchText) . '%';
    }
}

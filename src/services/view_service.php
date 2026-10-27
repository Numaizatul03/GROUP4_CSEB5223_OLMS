<?php

declare(strict_types=1);

/**
 * Class Name:  ViewService
 * Purpose:     Render view templates inside the shared page layout and redirect.
 * Description: Controllers pass data to a view by name, so no HTML is written
 *              inside controllers or services. Layout data (the logged-in user,
 *              cart count, current page) is set once by index.php and is
 *              available to every page.
 */
class ViewService
{
    private const VIEWS_PATH = __DIR__ . '/../views/';

    private static array $layoutData = [
        'currentUser' => null,
        'cartItemCount' => 0,
        'currentAction' => '',
        'currentSection' => '',
    ];

    /**
     * Function/Method Name: setLayoutData
     * Purpose:      Give the page layout the values every page needs.
     * Parameters:   array $layoutData - currentUser, cartItemCount, currentAction, currentSection
     * Return Value: void
     * Description:  Called by index.php before the controller runs.
     */
    public static function setLayoutData(array $layoutData): void
    {
        self::$layoutData = $layoutData + self::$layoutData;
    }

    /**
     * Function/Method Name: renderView
     * Purpose:      Show a page made of the header, the chosen view and the footer.
     * Parameters:   string $viewName - path under src/views without ".php", e.g. "products/list"
     *               array $viewData - variables made available inside the view
     * Return Value: void
     * Description:  extract() turns each array key into a variable for the template.
     */
    public static function renderView(string $viewName, array $viewData = []): void
    {
        extract($viewData + self::$layoutData, EXTR_SKIP);

        require self::VIEWS_PATH . 'layout/header.php';
        require self::VIEWS_PATH . $viewName . '.php';
        require self::VIEWS_PATH . 'layout/footer.php';
    }

    /**
     * Function/Method Name: renderPartial
     * Purpose:      Show a small reusable piece of a page (e.g. the product table).
     * Parameters:   string $partialName - path under src/views without ".php"
     *               array $partialData - variables made available inside the partial
     * Return Value: void
     * Description:  Lets several pages reuse the same markup instead of duplicating it.
     */
    public static function renderPartial(string $partialName, array $partialData = []): void
    {
        extract($partialData, EXTR_SKIP);

        require self::VIEWS_PATH . $partialName . '.php';
    }

    public static function redirectTo(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

/**
 * Function/Method Name: escapeHtml
 * Purpose:      Make text safe to print inside HTML.
 * Parameters:   mixed $value - text or number to print
 * Return Value: string - text with <, >, &, " and ' converted to HTML entities
 * Description:  Every value from the database or user is printed through this
 *               function to prevent cross-site scripting (XSS).
 */
function escapeHtml(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $price): string
{
    return CURRENCY_SYMBOL . ' ' . number_format($price, 2);
}

/**
 * Function/Method Name: formatDateTime
 * Purpose:      Show a database date-time in a friendly way.
 * Parameters:   ?string $dateTime - value such as "2026-10-25 20:05:00"
 * Return Value: string - e.g. "25 Oct 2026, 8:05 PM", or "-" when empty
 * Description:  Used for order, payment and account dates.
 */
function formatDateTime(?string $dateTime): string
{
    if ($dateTime === null || $dateTime === '') {
        return '-';
    }

    $timestamp = strtotime($dateTime);

    return $timestamp === false ? $dateTime : date('j M Y, g:i A', $timestamp);
}

/**
 * Function/Method Name: buildUrl
 * Purpose:      Build a link to a page of the system.
 * Parameters:   string $action - route name, e.g. "edit"
 *               array $parameters - extra query string values
 * Return Value: string - relative URL such as "index.php?action=edit&productId=3"
 * Description:  Relative URLs keep the system working both with "php -S" and in
 *               an XAMPP htdocs sub-folder.
 */
function buildUrl(string $action, array $parameters = []): string
{
    return 'index.php?' . http_build_query(['action' => $action] + $parameters);
}

/**
 * Function/Method Name: fieldErrorHtml
 * Purpose:      Print a form field's error message, if it has one.
 * Parameters:   array $errors - field name => message; string $fieldName
 * Return Value: string - a <p class="field-error"> element, or "" when there is no error
 * Description:  Keeps every form's error markup identical.
 */
function fieldErrorHtml(array $errors, string $fieldName): string
{
    return isset($errors[$fieldName]) ? '<p class="field-error">' . escapeHtml($errors[$fieldName]) . '</p>' : '';
}

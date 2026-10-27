<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/order.php';

/**
 * Class Name:  OrderValidator
 * Purpose:     Check user input for checkout, order search, payment search and quantities.
 * Description: Keeps every order-related input rule in one place.
 */
class OrderValidator
{
    public const MAX_ADDRESS_LENGTH = 255;

    private const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}$/';

    /**
     * Function/Method Name: validateCheckoutInput
     * Purpose:      Check the shipping address on the checkout form.
     * Parameters:   array $formInput - raw $_POST values
     * Return Value: array - field name => error message
     * Description:  The address is required and at most 255 characters.
     */
    public function validateCheckoutInput(array $formInput): array
    {
        $shippingAddress = trim((string) ($formInput['shippingAddress'] ?? ''));
        if ($shippingAddress === '') {
            return ['shippingAddress' => 'Please enter the delivery address.'];
        }

        return mb_strlen($shippingAddress) > self::MAX_ADDRESS_LENGTH
            ? ['shippingAddress' => 'Delivery address must be ' . self::MAX_ADDRESS_LENGTH . ' characters or fewer.']
            : [];
    }

    /**
     * Function/Method Name: parseQuantity
     * Purpose:      Turn a quantity field into a whole number.
     * Parameters:   mixed $quantityInput - raw form value
     * Return Value: ?int - the quantity, or null when it is not a whole number
     * Description:  CartService then checks the allowed range and the stock.
     */
    public function parseQuantity(mixed $quantityInput): ?int
    {
        $quantityText = trim((string) $quantityInput);

        return ctype_digit($quantityText) ? (int) $quantityText : null;
    }

    /**
     * Function/Method Name: validateOrderSearchInput
     * Purpose:      Check the Manage Orders search form.
     * Parameters:   array $queryInput - raw $_GET values
     * Return Value: array - field name => error message
     * Description:  Order ID must be a number, dates must be real dates (YYYY-MM-DD),
     *               the date range must not be reversed and the status must exist.
     */
    public function validateOrderSearchInput(array $queryInput): array
    {
        $errors = [];
        $orderIdText = trim((string) ($queryInput['orderId'] ?? ''));
        $orderStatus = trim((string) ($queryInput['orderStatus'] ?? ''));
        $dateFrom = trim((string) ($queryInput['dateFrom'] ?? ''));
        $dateTo = trim((string) ($queryInput['dateTo'] ?? ''));

        if ($orderIdText !== '' && !ctype_digit($orderIdText)) {
            $errors['orderId'] = 'Order ID must be a whole number, for example 3.';
        }

        if ($orderStatus !== '' && !in_array($orderStatus, Order::ORDER_STATUSES, true)) {
            $errors['orderStatus'] = 'Please choose a status from the list.';
        }

        foreach (['dateFrom' => $dateFrom, 'dateTo' => $dateTo] as $fieldName => $dateText) {
            if ($dateText !== '' && !$this->isValidDate($dateText)) {
                $errors[$fieldName] = 'Please enter a real date, for example 2026-10-25.';
            }
        }

        if (!isset($errors['dateFrom']) && !isset($errors['dateTo']) && $dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            $errors['dateTo'] = '"To" date must be on or after the "From" date.';
        }

        return $errors;
    }

    public function toOrderSearchCriteria(array $queryInput): array
    {
        $orderIdText = trim((string) ($queryInput['orderId'] ?? ''));

        return [
            'orderId' => $orderIdText === '' ? null : (int) $orderIdText,
            'keyword' => trim((string) ($queryInput['keyword'] ?? '')),
            'orderStatus' => trim((string) ($queryInput['orderStatus'] ?? '')),
            'dateFrom' => trim((string) ($queryInput['dateFrom'] ?? '')),
            'dateTo' => trim((string) ($queryInput['dateTo'] ?? '')),
        ];
    }

    /**
     * Function/Method Name: validatePaymentSearchInput
     * Purpose:      Check the Payments search form.
     * Parameters:   array $queryInput - raw $_GET values
     * Return Value: array - field name => error message
     * Description:  Method and status must be values from the lists.
     */
    public function validatePaymentSearchInput(array $queryInput): array
    {
        $errors = [];
        $orderIdText = trim((string) ($queryInput['orderId'] ?? ''));
        $paymentMethod = (string) ($queryInput['paymentMethod'] ?? '');
        $paymentStatus = (string) ($queryInput['paymentStatus'] ?? '');

        if ($orderIdText !== '' && !ctype_digit($orderIdText)) {
            $errors['orderId'] = 'Order ID must be a whole number.';
        }

        if ($paymentMethod !== '' && !in_array($paymentMethod, Payment::PAYMENT_METHODS, true)) {
            $errors['paymentMethod'] = 'Please choose a method from the list.';
        }

        if ($paymentStatus !== '' && !in_array($paymentStatus, Payment::PAYMENT_STATUSES, true)) {
            $errors['paymentStatus'] = 'Please choose a status from the list.';
        }

        return $errors;
    }

    public function toPaymentSearchCriteria(array $queryInput): array
    {
        $orderIdText = trim((string) ($queryInput['orderId'] ?? ''));

        return [
            'orderId' => $orderIdText === '' ? null : (int) $orderIdText,
            'paymentMethod' => (string) ($queryInput['paymentMethod'] ?? ''),
            'paymentStatus' => (string) ($queryInput['paymentStatus'] ?? ''),
        ];
    }

    /**
     * Function/Method Name: hasSearchCriteria
     * Purpose:      Tell whether at least one search field was filled in.
     * Parameters:   array $searchCriteria
     * Return Value: bool
     * Description:  Shared by every search form so "Not found" only appears after a real search.
     */
    public static function hasSearchCriteria(array $searchCriteria): bool
    {
        foreach ($searchCriteria as $criterionValue) {
            if ($criterionValue !== null && $criterionValue !== '') {
                return true;
            }
        }

        return false;
    }

    private function isValidDate(string $dateText): bool
    {
        if (!preg_match(self::DATE_PATTERN, $dateText)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $dateText));

        return checkdate($month, $day, $year);
    }
}

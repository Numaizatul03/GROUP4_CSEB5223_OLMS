<?php

declare(strict_types=1);

require_once __DIR__ . '/user.php';

/**
 * Class Name:  Customer
 * Purpose:     A user who browses and buys products (Assignment Part 1, Section 3.2).
 * Description: Inherits from User and adds the shipping address and phone
 *              number. The shopping methods from the class diagram
 *              (browseProducts, searchProducts, addToCart, checkout, viewOrders)
 *              are carried out by ShopController, CartService and OrderService.
 */
class Customer extends User
{
    private string $shippingAddress = '';
    private string $phoneNumber = '';

    public function getRole(): string
    {
        return self::ROLE_CUSTOMER;
    }

    /**
     * Function/Method Name: getUserDetails
     * Purpose:      Return the customer's attributes in one array.
     * Parameters:   none
     * Return Value: array - User details plus shippingAddress and phoneNumber
     * Description:  Overrides User::getUserDetails() to include customer fields.
     */
    public function getUserDetails(): array
    {
        return parent::getUserDetails() + [
            'shippingAddress' => $this->shippingAddress,
            'phoneNumber' => $this->phoneNumber,
        ];
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $shippingAddress): void
    {
        $this->shippingAddress = trim($shippingAddress);
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = trim($phoneNumber);
    }
}

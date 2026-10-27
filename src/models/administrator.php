<?php

declare(strict_types=1);

require_once __DIR__ . '/user.php';

/**
 * Class Name:  Administrator
 * Purpose:     A user who manages the system (Assignment Part 1, Section 3.3).
 * Description: Inherits from User. The management methods from the class
 *              diagram (manageProducts, manageCategories, manageInventory,
 *              manageCustomers, manageOrders) are the administrator pages,
 *              which the router only opens for this role.
 */
class Administrator extends User
{
    public function getRole(): string
    {
        return self::ROLE_ADMINISTRATOR;
    }
}

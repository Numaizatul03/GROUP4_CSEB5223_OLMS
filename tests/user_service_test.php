<?php

declare(strict_types=1);

/**
 * File:    user_service_test.php
 * Purpose: Check registration, login, profile updates and customer management.
 */

const NEW_CUSTOMER_INPUT = [
    'name' => 'Aina Sofea',
    'email' => 'Aina@Example.com',
    'phoneNumber' => '011-2233445',
    'shippingAddress' => '3, Jalan Kenanga, 43650 Bandar Baru Bangi, Selangor',
    'password' => 'Bangi2026',
    'passwordConfirmation' => 'Bangi2026',
];

function testRegisterCustomerStoresHashedPassword(): void
{
    $connection = createTestConnection();
    $userService = new UserService($connection);
    $userValidator = new UserValidator();

    assertSame([], $userValidator->validateCustomerInput(NEW_CUSTOMER_INPUT, false, true), 'Valid registration should pass');
    $newUserId = $userService->createUser($userValidator->toUserData(NEW_CUSTOMER_INPUT));
    $storedHash = $connection->query('SELECT password_hash FROM users WHERE user_id = ' . $newUserId)->fetchColumn();
    $customer = $userService->findUserById($newUserId);

    assertTrue($customer instanceof Customer, 'A registered user should be a Customer');
    assertSame('aina@example.com', $customer->getEmail(), 'Email should be stored in lower case');
    assertTrue($storedHash !== 'Bangi2026' && password_verify('Bangi2026', $storedHash), 'Only a hash of the password should be stored');
}

function testRegistrationRejectsDuplicateEmailAndWeakPassword(): void
{
    $userService = new UserService(createTestConnection());
    $userValidator = new UserValidator();
    $formInput = ['email' => 'SITI@olms.test', 'password' => 'short', 'passwordConfirmation' => 'other'] + NEW_CUSTOMER_INPUT;
    $validationErrors = $userValidator->validateCustomerInput($formInput, $userService->isEmailTaken($formInput['email']), true);

    assertTrue(str_contains($validationErrors['email'] ?? '', 'already registered'), 'Email should be refused whatever the case');
    assertTrue(isset($validationErrors['password']), 'Short password should be refused');
    assertTrue(isset($validationErrors['passwordConfirmation']), 'Mismatched confirmation should be refused');
}

function testLoginChecksEmailAndPassword(): void
{
    $authService = new AuthService(new UserService(createTestConnection()));
    $_SESSION = [];

    assertSame(null, $authService->loginUser('siti@olms.test', 'WrongPass1'), 'Wrong password should fail');
    assertSame(null, $authService->loginUser('nobody@olms.test', 'Customer@123'), 'Unknown email should fail');

    $administrator = $authService->loginUser('ADMIN@olms.test', 'Admin@123');
    assertTrue($administrator instanceof Administrator, 'Admin login should return an Administrator');
    assertTrue($authService->hasAccess('administrator') && !$authService->hasAccess('customer'), 'Role access should follow the login');

    $authService->logoutUser();
    assertSame(null, $authService->getCurrentUser(), 'Nobody should be logged in after logout');
    assertTrue($authService->hasAccess('guest'), 'Guest pages should open again after logout');
}

function testUpdateProfileAndPassword(): void
{
    $userService = new UserService(createTestConnection());
    $customer = $userService->findUserById(2);
    $customer->setPhoneNumber('012-9999999');
    $customer->setShippingAddress('New address, 43000 Kajang');

    assertTrue($userService->updateProfile($customer), 'Profile update should succeed');
    $userService->updatePassword(2, 'NewSecret99');
    $reloadedCustomer = $userService->findUserById(2);

    assertSame('012-9999999', $reloadedCustomer->getPhoneNumber(), 'New phone number should be saved');
    assertTrue($reloadedCustomer->verifyPassword('NewSecret99'), 'New password should work');
    assertTrue(!$reloadedCustomer->verifyPassword('Customer@123'), 'Old password should stop working');
}

function testSearchAndDeleteCustomers(): void
{
    $userService = new UserService(createTestConnection());

    assertSame(3, count($userService->getAllCustomers()), 'Sample data has 3 customers (admin excluded)');
    assertSame('Daniel Lim Wei Jie', $userService->searchCustomers(['keyword' => '017-222'])[0]->getName(), 'Phone search should work');
    assertSame([], $userService->searchCustomers(['keyword' => 'zzz']), 'Unknown keyword should find nobody');
    assertThrows(fn () => $userService->deleteCustomer(2), 'has orders', 'Customer with orders must not be deleted');

    $newUserId = $userService->createUser((new UserValidator())->toUserData(NEW_CUSTOMER_INPUT));
    assertTrue($userService->deleteCustomer($newUserId), 'Customer without orders can be deleted');
    assertSame(null, $userService->findUserById($newUserId), 'Deleted customer should be gone');
    assertTrue(!$userService->deleteCustomer(1), 'The administrator must never be deleted through Manage Customers');
}

return [
    'testRegisterCustomerStoresHashedPassword',
    'testRegistrationRejectsDuplicateEmailAndWeakPassword',
    'testLoginChecksEmailAndPassword',
    'testUpdateProfileAndPassword',
    'testSearchAndDeleteCustomers',
];

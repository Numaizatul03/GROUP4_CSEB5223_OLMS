<?php

declare(strict_types=1);

/**
 * Class Name:  UserValidator
 * Purpose:     Check and convert user input for registration, login, profiles and customer search.
 * Description: Works only on the submitted values; facts that need the database
 *              (for example "is this email already registered?") are passed in
 *              by the controller, so this class stays easy to test.
 */
class UserValidator
{
    public const MAX_NAME_LENGTH = 100;
    public const MAX_EMAIL_LENGTH = 150;
    public const MAX_ADDRESS_LENGTH = 255;
    public const MIN_PASSWORD_LENGTH = 8;

    private const PHONE_PATTERN = '/^[0-9+\-\s]{7,20}$/';
    private const WHOLE_NUMBER_PATTERN = '/^\d+$/';

    /**
     * Function/Method Name: validateCustomerInput
     * Purpose:      Check the registration form or a customer profile form.
     * Parameters:   array $formInput - raw $_POST values
     *               bool $isEmailTaken - result of UserService::isEmailTaken()
     *               bool $isPasswordRequired - true for registration / new customer
     * Return Value: array - field name => error message (empty array when valid)
     * Description:  Each field has its own small check method below.
     */
    public function validateCustomerInput(array $formInput, bool $isEmailTaken, bool $isPasswordRequired): array
    {
        $errors = [
            'name' => $this->validateName((string) ($formInput['name'] ?? '')),
            'email' => $this->validateEmail((string) ($formInput['email'] ?? ''), $isEmailTaken),
            'phoneNumber' => $this->validatePhoneNumber((string) ($formInput['phoneNumber'] ?? '')),
            'shippingAddress' => $this->validateAddress((string) ($formInput['shippingAddress'] ?? '')),
        ];

        if ($isPasswordRequired) {
            $errors += $this->validateNewPassword($formInput);
        }

        return array_filter($errors, static fn (?string $errorMessage) => $errorMessage !== null);
    }

    /**
     * Function/Method Name: validateAdministratorInput
     * Purpose:      Check an administrator's own profile form (name and email only).
     * Parameters:   array $formInput, bool $isEmailTaken
     * Return Value: array - field name => error message
     * Description:  Administrators have no shipping address or phone number.
     */
    public function validateAdministratorInput(array $formInput, bool $isEmailTaken): array
    {
        $errors = [
            'name' => $this->validateName((string) ($formInput['name'] ?? '')),
            'email' => $this->validateEmail((string) ($formInput['email'] ?? ''), $isEmailTaken),
        ];

        return array_filter($errors, static fn (?string $errorMessage) => $errorMessage !== null);
    }

    /**
     * Function/Method Name: validateLoginInput
     * Purpose:      Check that the login form is filled in.
     * Parameters:   array $formInput - raw $_POST values
     * Return Value: array - field name => error message
     * Description:  Whether the password is correct is checked by AuthService.
     */
    public function validateLoginInput(array $formInput): array
    {
        $errors = [];
        if (trim((string) ($formInput['email'] ?? '')) === '') {
            $errors['email'] = 'Please enter your email address.';
        }

        if ((string) ($formInput['password'] ?? '') === '') {
            $errors['password'] = 'Please enter your password.';
        }

        return $errors;
    }

    /**
     * Function/Method Name: validateNewPassword
     * Purpose:      Check a new password and its confirmation.
     * Parameters:   array $formInput - needs "password" and "passwordConfirmation"
     * Return Value: array - field name => error message
     * Description:  At least 8 characters with at least one letter and one number.
     */
    public function validateNewPassword(array $formInput): array
    {
        $password = (string) ($formInput['password'] ?? '');
        $errors = [];

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must contain at least one letter and one number.';
        }

        if ($password !== (string) ($formInput['passwordConfirmation'] ?? '')) {
            $errors['passwordConfirmation'] = 'The two passwords do not match.';
        }

        return $errors;
    }

    /**
     * Function/Method Name: toUserData
     * Purpose:      Convert a valid form into clean values for UserService.
     * Parameters:   array $formInput - raw $_POST values that passed validation
     * Return Value: array - name, email, password, shippingAddress, phoneNumber (trimmed)
     * Description:  The email is lower-cased so it is stored in one consistent form.
     */
    public function toUserData(array $formInput): array
    {
        return [
            'name' => trim((string) ($formInput['name'] ?? '')),
            'email' => strtolower(trim((string) ($formInput['email'] ?? ''))),
            'password' => (string) ($formInput['password'] ?? ''),
            'shippingAddress' => trim((string) ($formInput['shippingAddress'] ?? '')),
            'phoneNumber' => trim((string) ($formInput['phoneNumber'] ?? '')),
        ];
    }

    /**
     * Function/Method Name: validateSearchInput
     * Purpose:      Check the Manage Customers search form.
     * Parameters:   array $queryInput - raw $_GET values
     * Return Value: array - field name => error message
     * Description:  Customer ID, when given, must be a whole number.
     */
    public function validateSearchInput(array $queryInput): array
    {
        $customerIdText = trim((string) ($queryInput['customerId'] ?? ''));
        if ($customerIdText !== '' && !preg_match(self::WHOLE_NUMBER_PATTERN, $customerIdText)) {
            return ['customerId' => 'Customer ID must be a whole number, for example 2.'];
        }

        return [];
    }

    public function toSearchCriteria(array $queryInput): array
    {
        $customerIdText = trim((string) ($queryInput['customerId'] ?? ''));

        return [
            'customerId' => $customerIdText === '' ? null : (int) $customerIdText,
            'keyword' => trim((string) ($queryInput['keyword'] ?? '')),
        ];
    }

    private function validateName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Name is required.';
        }

        return mb_strlen($name) > self::MAX_NAME_LENGTH ? 'Name must be ' . self::MAX_NAME_LENGTH . ' characters or fewer.' : null;
    }

    private function validateEmail(string $email, bool $isEmailTaken): ?string
    {
        $email = trim($email);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Please enter a valid email address, for example name@example.com.';
        }

        if (mb_strlen($email) > self::MAX_EMAIL_LENGTH) {
            return 'Email must be ' . self::MAX_EMAIL_LENGTH . ' characters or fewer.';
        }

        return $isEmailTaken ? 'This email is already registered. Please log in or use another email.' : null;
    }

    private function validatePhoneNumber(string $phoneNumber): ?string
    {
        return preg_match(self::PHONE_PATTERN, trim($phoneNumber))
            ? null
            : 'Phone number must be 7-20 digits, for example 012-3456789.';
    }

    private function validateAddress(string $shippingAddress): ?string
    {
        $shippingAddress = trim($shippingAddress);
        if ($shippingAddress === '') {
            return 'Shipping address is required so we know where to deliver.';
        }

        return mb_strlen($shippingAddress) > self::MAX_ADDRESS_LENGTH
            ? 'Shipping address must be ' . self::MAX_ADDRESS_LENGTH . ' characters or fewer.'
            : null;
    }
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/business_rule_exception.php';

/**
 * Class Name:  UserService
 * Purpose:     Create, read, update, delete and search users (customers and administrators).
 * Description: All SQL for the users table is kept here. Passwords are stored
 *              only as bcrypt hashes made by password_hash().
 */
class UserService
{
    private const SELECT_USERS_SQL = 'SELECT user_id, name, email, password_hash, role, shipping_address,
                phone_number, created_at, updated_at
            FROM users';

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Function/Method Name: findUserById
     * Purpose:      Fetch one user by ID.
     * Parameters:   int $userId
     * Return Value: ?User - a Customer or Administrator, or null when not found
     * Description:  Used for the logged-in user and the customer pages.
     */
    public function findUserById(int $userId): ?User
    {
        $statement = $this->connection->prepare(self::SELECT_USERS_SQL . ' WHERE user_id = :userId');
        $statement->execute(['userId' => $userId]);
        $userRow = $statement->fetch();

        return $userRow === false ? null : User::fromDatabaseRow($userRow);
    }

    /**
     * Function/Method Name: findUserByEmail
     * Purpose:      Fetch one user by email address.
     * Parameters:   string $email - compared without case (Siti@... = siti@...)
     * Return Value: ?User - the user, or null when no account uses that email
     * Description:  Used by login and by the "email already registered" check.
     */
    public function findUserByEmail(string $email): ?User
    {
        $statement = $this->connection->prepare(self::SELECT_USERS_SQL . ' WHERE LOWER(email) = :email');
        $statement->execute(['email' => strtolower(trim($email))]);
        $userRow = $statement->fetch();

        return $userRow === false ? null : User::fromDatabaseRow($userRow);
    }

    /**
     * Function/Method Name: isEmailTaken
     * Purpose:      Check whether another account already uses an email.
     * Parameters:   string $email, ?int $exceptUserId - the user's own ID when editing
     * Return Value: bool - true when the email belongs to a different account
     * Description:  Lets a user keep their own email when updating the profile.
     */
    public function isEmailTaken(string $email, ?int $exceptUserId = null): bool
    {
        $existingUser = $this->findUserByEmail($email);

        return $existingUser !== null && $existingUser->getUserId() !== $exceptUserId;
    }

    /**
     * Function/Method Name: getAllCustomers
     * Purpose:      Fetch every customer for the Manage Customers page.
     * Parameters:   none
     * Return Value: Customer[] - ordered by user ID
     * Description:  Administrators are not included.
     */
    public function getAllCustomers(): array
    {
        return $this->searchCustomers([]);
    }

    /**
     * Function/Method Name: searchCustomers
     * Purpose:      Find customers that match the search criteria.
     * Parameters:   array $searchCriteria - customerId (int) and/or keyword (string); null/'' = not used
     * Return Value: Customer[] - matching customers (empty array when nothing matches)
     * Description:  The keyword is matched against name, email and phone number.
     */
    public function searchCustomers(array $searchCriteria): array
    {
        $conditions = ['role = :role'];
        $parameters = ['role' => User::ROLE_CUSTOMER];

        if (($searchCriteria['customerId'] ?? null) !== null) {
            $conditions[] = 'user_id = :userId';
            $parameters['userId'] = $searchCriteria['customerId'];
        }

        if (($searchCriteria['keyword'] ?? '') !== '') {
            $conditions[] = '(name LIKE :keywordName OR email LIKE :keywordEmail OR phone_number LIKE :keywordPhone)';
            $parameters['keywordName'] = '%' . $searchCriteria['keyword'] . '%';
            $parameters['keywordEmail'] = '%' . $searchCriteria['keyword'] . '%';
            $parameters['keywordPhone'] = '%' . $searchCriteria['keyword'] . '%';
        }

        $statement = $this->connection->prepare(
            self::SELECT_USERS_SQL . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY user_id'
        );
        $statement->execute($parameters);

        return array_map(static fn (array $userRow) => User::fromDatabaseRow($userRow), $statement->fetchAll());
    }

    /**
     * Function/Method Name: createUser
     * Purpose:      Insert a new user account.
     * Parameters:   array $userData - name, email, password (plain), shippingAddress, phoneNumber
     *               string $role - User::ROLE_CUSTOMER or User::ROLE_ADMINISTRATOR
     * Return Value: int - ID of the new user
     * Description:  The password is hashed here and the plain text is never stored.
     */
    public function createUser(array $userData, string $role = User::ROLE_CUSTOMER): int
    {
        $currentTime = date('Y-m-d H:i:s');
        $statement = $this->connection->prepare(
            'INSERT INTO users (name, email, password_hash, role, shipping_address, phone_number, created_at, updated_at)
             VALUES (:name, :email, :passwordHash, :role, :shippingAddress, :phoneNumber, :createdAt, :updatedAt)'
        );
        $statement->execute([
            'name' => $userData['name'],
            'email' => strtolower($userData['email']),
            'passwordHash' => password_hash($userData['password'], PASSWORD_DEFAULT),
            'role' => $role,
            'shippingAddress' => $userData['shippingAddress'] ?? '',
            'phoneNumber' => $userData['phoneNumber'] ?? '',
            'createdAt' => $currentTime,
            'updatedAt' => $currentTime,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Function/Method Name: updateProfile
     * Purpose:      Save changed profile fields (updateProfile() in the class diagram).
     * Parameters:   User $user - user object that already holds the new values
     * Return Value: bool - true when the user still exists after saving
     * Description:  Administrators have no address or phone, so those stay empty for them.
     */
    public function updateProfile(User $user): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE users SET name = :name, email = :email, shipping_address = :shippingAddress,
                 phone_number = :phoneNumber, updated_at = :updatedAt
             WHERE user_id = :userId'
        );
        $statement->execute([
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'shippingAddress' => $user instanceof Customer ? $user->getShippingAddress() : '',
            'phoneNumber' => $user instanceof Customer ? $user->getPhoneNumber() : '',
            'updatedAt' => date('Y-m-d H:i:s'),
            'userId' => $user->getUserId(),
        ]);

        return $this->findUserById((int) $user->getUserId()) !== null;
    }

    /**
     * Function/Method Name: updatePassword
     * Purpose:      Replace a user's password.
     * Parameters:   int $userId, string $newPassword - plain text, validated by UserValidator
     * Return Value: void
     * Description:  Stores a fresh bcrypt hash.
     */
    public function updatePassword(int $userId, string $newPassword): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users SET password_hash = :passwordHash, updated_at = :updatedAt WHERE user_id = :userId'
        );
        $statement->execute([
            'passwordHash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updatedAt' => date('Y-m-d H:i:s'),
            'userId' => $userId,
        ]);
    }

    /**
     * Function/Method Name: deleteCustomer
     * Purpose:      Permanently remove a customer account.
     * Parameters:   int $customerId
     * Return Value: bool - true when a customer row was deleted
     * Description:  Refused while the customer has orders, because order records
     *               must be kept. The cart is removed automatically (ON DELETE CASCADE).
     */
    public function deleteCustomer(int $customerId): bool
    {
        if ($this->countOrdersForUser($customerId) > 0) {
            throw new BusinessRuleException('This customer has orders, so the account cannot be deleted. Order records must be kept.');
        }

        $statement = $this->connection->prepare('DELETE FROM users WHERE user_id = :userId AND role = :role');
        $statement->execute(['userId' => $customerId, 'role' => User::ROLE_CUSTOMER]);

        return $statement->rowCount() > 0;
    }

    public function countOrdersForUser(int $userId): int
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM orders WHERE user_id = :userId');
        $statement->execute(['userId' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public function countCustomers(): int
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $statement->execute(['role' => User::ROLE_CUSTOMER]);

        return (int) $statement->fetchColumn();
    }
}

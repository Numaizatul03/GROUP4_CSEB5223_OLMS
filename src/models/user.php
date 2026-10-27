<?php

declare(strict_types=1);

/**
 * Class Name:  User
 * Purpose:     Hold the information shared by every user of the system.
 * Description: Follows the User class in the class diagram (Assignment Part 1,
 *              Section 3.1). Customer and Administrator inherit from it.
 *              login(), logout() and updateProfile() from the diagram are
 *              implemented by AuthService and UserService, because they need
 *              the session and the database; this class only holds data.
 */
abstract class User
{
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_ADMINISTRATOR = 'administrator';

    private ?int $userId = null;
    private string $name = '';
    private string $email = '';
    private string $passwordHash = '';
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    /**
     * Function/Method Name: getRole
     * Purpose:      Tell which kind of user this is.
     * Parameters:   none
     * Return Value: string - ROLE_CUSTOMER or ROLE_ADMINISTRATOR
     * Description:  Each subclass returns its own role, so the role can never
     *               disagree with the object type.
     */
    abstract public function getRole(): string;

    /**
     * Function/Method Name: fromDatabaseRow
     * Purpose:      Build the right kind of user (Customer or Administrator) from a users row.
     * Parameters:   array $userRow - associative row from the users table
     * Return Value: User - a Customer or an Administrator object
     * Description:  The "role" column decides which subclass is created.
     */
    public static function fromDatabaseRow(array $userRow): User
    {
        if ($userRow['role'] === self::ROLE_ADMINISTRATOR) {
            $user = new Administrator();
        } else {
            $user = new Customer();
            $user->setShippingAddress((string) $userRow['shipping_address']);
            $user->setPhoneNumber((string) $userRow['phone_number']);
        }

        $user->setUserId((int) $userRow['user_id']);
        $user->setName((string) $userRow['name']);
        $user->setEmail((string) $userRow['email']);
        $user->passwordHash = (string) $userRow['password_hash'];
        $user->createdAt = $userRow['created_at'] ?? null;
        $user->updatedAt = $userRow['updated_at'] ?? null;

        return $user;
    }

    /**
     * Function/Method Name: verifyPassword
     * Purpose:      Check a typed password against the stored hash.
     * Parameters:   string $plainPassword - password typed on the login form
     * Return Value: bool - true when the password is correct
     * Description:  Passwords are never stored in plain text; password_verify()
     *               compares against the bcrypt hash.
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * Function/Method Name: getUserDetails
     * Purpose:      Return the user's attributes in one array for display.
     * Parameters:   none
     * Return Value: array - attribute name => value (never includes the password hash)
     * Description:  Customer adds its own fields on top of these.
     */
    public function getUserDetails(): array
    {
        return [
            'userId' => $this->userId,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->getRole(),
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->getRole() === self::ROLE_ADMINISTRATOR;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower(trim($email));
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}

// Subclasses are loaded after User exists, because they extend it.
require_once __DIR__ . '/customer.php';
require_once __DIR__ . '/administrator.php';

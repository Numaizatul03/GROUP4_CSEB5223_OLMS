<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/../services/auth_service.php';
require_once __DIR__ . '/../services/user_validator.php';

/**
 * Class Name:  AuthController
 * Purpose:     Register, log in, log out and update the profile (User, Customer, Administrator).
 * Description: Covers the "Register and Log In" use case for customers and the
 *              "Log In" use case for administrators.
 */
class AuthController extends BaseController
{
    private AuthService $authService;
    private UserService $userService;
    private UserValidator $userValidator;

    public function __construct(AuthService $authService, UserService $userService, UserValidator $userValidator)
    {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->userValidator = $userValidator;
    }

    public function showLoginForm(): void
    {
        $this->renderLoginForm([], []);
    }

    /**
     * Function/Method Name: loginUser
     * Purpose:      Check the login form and log the user in.
     * Parameters:   none (reads email and password from $_POST)
     * Return Value: void
     * Description:  A wrong email or password shows one general message. After
     *               logging in, the user returns to the page they first asked for,
     *               or to their home page (shop or dashboard).
     */
    public function loginUser(): void
    {
        $this->requireValidCsrfToken();
        $validationErrors = $this->userValidator->validateLoginInput($_POST);
        $user = $validationErrors === [] ? $this->authService->loginUser((string) $_POST['email'], (string) $_POST['password']) : null;

        if ($user === null) {
            http_response_code(422);
            $this->renderLoginForm($_POST, $validationErrors ?: ['login' => 'Incorrect email or password. Please try again.']);
            return;
        }

        $returnUrl = (string) ($_POST['returnUrl'] ?? '');
        $homeUrl = buildUrl($user->isAdministrator() ? 'dashboard' : 'shop');
        // Only our own relative links are accepted, so a crafted link cannot send the user to another website.
        $isSafeReturnUrl = str_starts_with($returnUrl, 'index.php?') && !str_contains($returnUrl, '//');
        $this->redirectWithSuccess($isSafeReturnUrl ? $returnUrl : $homeUrl, 'Welcome back, ' . $user->getName() . '!');
    }

    public function showRegisterForm(): void
    {
        $this->renderRegisterForm([], []);
    }

    /**
     * Function/Method Name: registerCustomer
     * Purpose:      Create a customer account and log the new customer in.
     * Parameters:   none (reads the form from $_POST)
     * Return Value: void
     * Description:  Invalid input re-shows the form with messages. New accounts are
     *               always customers; administrator accounts cannot be self-registered.
     */
    public function registerCustomer(): void
    {
        $this->requireValidCsrfToken();
        $isEmailTaken = $this->userService->isEmailTaken((string) ($_POST['email'] ?? ''));
        $validationErrors = $this->userValidator->validateCustomerInput($_POST, $isEmailTaken, true);
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderRegisterForm($_POST, $validationErrors);
            return;
        }

        $userData = $this->userValidator->toUserData($_POST);
        $this->userService->createUser($userData, User::ROLE_CUSTOMER);
        $this->authService->loginUser($userData['email'], $userData['password']);
        $this->redirectWithSuccess(buildUrl('shop'), 'Your account was created. Happy shopping, ' . $userData['name'] . '!');
    }

    public function logoutUser(): void
    {
        $this->requireValidCsrfToken();
        $this->authService->logoutUser();
        $this->redirectWithSuccess(buildUrl('login'), 'You have logged out.');
    }

    /**
     * Function/Method Name: showProfile
     * Purpose:      Show the logged-in user's details with the edit and password forms.
     * Parameters:   none
     * Return Value: void
     * Description:  Customers also see their address and phone fields.
     */
    public function showProfile(): void
    {
        $currentUser = $this->authService->getCurrentUser();
        $this->renderProfile($currentUser->getUserDetails(), [], []);
    }

    /**
     * Function/Method Name: updateProfile
     * Purpose:      Save the profile form (updateProfile() in the class diagram).
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  Keeps the old values so the result page shows before and after.
     */
    public function updateProfile(): void
    {
        $this->requireValidCsrfToken();
        $currentUser = $this->authService->getCurrentUser();
        $isEmailTaken = $this->userService->isEmailTaken((string) ($_POST['email'] ?? ''), $currentUser->getUserId());
        $validationErrors = $currentUser instanceof Customer
            ? $this->userValidator->validateCustomerInput($_POST, $isEmailTaken, false)
            : $this->userValidator->validateAdministratorInput($_POST, $isEmailTaken);

        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderProfile($_POST + $currentUser->getUserDetails(), $validationErrors, []);
            return;
        }

        $previousDetails = $currentUser->getUserDetails();
        $userData = $this->userValidator->toUserData($_POST);
        $currentUser->setName($userData['name']);
        $currentUser->setEmail($userData['email']);
        if ($currentUser instanceof Customer) {
            $currentUser->setShippingAddress($userData['shippingAddress']);
            $currentUser->setPhoneNumber($userData['phoneNumber']);
        }
        $this->userService->updateProfile($currentUser);

        SessionService::setFlashData('previousDetails', $previousDetails);
        ViewService::redirectTo(buildUrl('profile_result'));
    }

    /**
     * Function/Method Name: showProfileResult
     * Purpose:      Display the saved profile next to the old values.
     * Parameters:   none
     * Return Value: void
     * Description:  The "after" values are read again from the database.
     */
    public function showProfileResult(): void
    {
        $currentUser = $this->userService->findUserById((int) $this->authService->getCurrentUser()->getUserId());

        ViewService::renderView('shared/update_result', [
            'pageTitle' => 'Profile Updated',
            'successMessage' => 'Your profile was saved at ' . formatDateTime($currentUser->getUpdatedAt()) . '.',
            'fieldLabels' => UserFieldLabels::forUser($currentUser),
            'previousDetails' => SessionService::pullFlashData('previousDetails'),
            'currentDetails' => $currentUser->getUserDetails(),
            'backLinks' => ['Back to my profile' => buildUrl('profile')],
        ]);
    }

    /**
     * Function/Method Name: updatePassword
     * Purpose:      Change the logged-in user's password.
     * Parameters:   none (reads currentPassword, password, passwordConfirmation from $_POST)
     * Return Value: void
     * Description:  The current password must be typed again, so someone using an
     *               unattended logged-in browser cannot change it.
     */
    public function updatePassword(): void
    {
        $this->requireValidCsrfToken();
        $currentUser = $this->authService->getCurrentUser();
        $passwordErrors = $this->userValidator->validateNewPassword($_POST);
        if (!$currentUser->verifyPassword((string) ($_POST['currentPassword'] ?? ''))) {
            $passwordErrors = ['currentPassword' => 'Your current password is not correct.'] + $passwordErrors;
        }

        if ($passwordErrors !== []) {
            http_response_code(422);
            $this->renderProfile($currentUser->getUserDetails(), [], $passwordErrors);
            return;
        }

        $this->userService->updatePassword((int) $currentUser->getUserId(), (string) $_POST['password']);
        $this->redirectWithSuccess(buildUrl('profile'), 'Your password was changed.');
    }

    private function renderLoginForm(array $formInput, array $validationErrors): void
    {
        ViewService::renderView('auth/login', [
            'pageTitle' => 'Log In',
            'formInput' => $formInput,
            'validationErrors' => $validationErrors,
            'returnUrl' => (string) ($formInput['returnUrl'] ?? SessionService::pullFlashData('returnUrl') ?? ''),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    private function renderRegisterForm(array $formInput, array $validationErrors): void
    {
        ViewService::renderView('auth/register', [
            'pageTitle' => 'Create an Account',
            'formInput' => $formInput,
            'validationErrors' => $validationErrors,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    private function renderProfile(array $profileInput, array $profileErrors, array $passwordErrors): void
    {
        $currentUser = $this->authService->getCurrentUser();
        ViewService::renderView('auth/profile', [
            'pageTitle' => 'My Profile',
            'currentUser' => $currentUser,
            'profileInput' => $profileInput,
            'profileErrors' => $profileErrors,
            'passwordErrors' => $passwordErrors,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }
}

/**
 * Class Name:  UserFieldLabels
 * Purpose:     The labels shown for user attributes on detail and before/after pages.
 * Description: Shared by the profile and Manage Customers pages.
 */
final class UserFieldLabels
{
    public static function forUser(User $user): array
    {
        $fieldLabels = ['name' => 'Name', 'email' => 'Email'];
        if ($user instanceof Customer) {
            $fieldLabels += ['phoneNumber' => 'Phone Number', 'shippingAddress' => 'Shipping Address'];
        }

        return $fieldLabels;
    }
}

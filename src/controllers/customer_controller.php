<?php

declare(strict_types=1);

require_once __DIR__ . '/base_controller.php';
require_once __DIR__ . '/auth_controller.php';
require_once __DIR__ . '/../services/user_service.php';
require_once __DIR__ . '/../services/user_validator.php';
require_once __DIR__ . '/../services/order_service.php';
require_once __DIR__ . '/../services/order_validator.php';

/**
 * Class Name:  CustomerController
 * Purpose:     The "Manage Customers" use case: create, search, view, edit, delete, display.
 * Description: Administrators can add a customer (for example a walk-in buyer),
 *              correct details and remove accounts that have no orders.
 */
class CustomerController extends BaseController
{
    private UserService $userService;
    private UserValidator $userValidator;
    private OrderService $orderService;

    public function __construct(UserService $userService, UserValidator $userValidator, OrderService $orderService)
    {
        $this->userService = $userService;
        $this->userValidator = $userValidator;
        $this->orderService = $orderService;
    }

    /**
     * Function/Method Name: listCustomers
     * Purpose:      Display all customers, with search by ID or name/email/phone.
     * Parameters:   none (reads customerId and keyword from $_GET)
     * Return Value: void
     * Description:  A search with no match shows "Not found"; the full list follows.
     */
    public function listCustomers(): void
    {
        $searchErrors = $this->userValidator->validateSearchInput($_GET);
        $searchCriteria = $this->userValidator->toSearchCriteria($_GET);
        $hasSearched = $searchErrors === [] && OrderValidator::hasSearchCriteria($searchCriteria);

        ViewService::renderView('customers/list', [
            'pageTitle' => 'Manage Customers',
            'searchInput' => $_GET,
            'searchErrors' => $searchErrors,
            'hasSearched' => $hasSearched,
            'searchResults' => $hasSearched ? $this->userService->searchCustomers($searchCriteria) : [],
            'customers' => $this->userService->getAllCustomers(),
            'orderCounts' => $this->countOrdersPerCustomer(),
            'highlightCustomerId' => SessionService::pullFlashData('highlightCustomerId'),
        ]);
    }

    /**
     * Function/Method Name: viewCustomer
     * Purpose:      Show one customer's details and their orders.
     * Parameters:   none (reads customerId from $_GET)
     * Return Value: void
     * Description:  Links the Customer and Order modules on one page.
     */
    public function viewCustomer(): void
    {
        $customer = $this->findRequestedCustomer($_GET);
        if ($customer === null) {
            return;
        }

        ViewService::renderView('customers/view', [
            'pageTitle' => $customer->getName(),
            'customer' => $customer,
            'detailRows' => $this->toDetailRows($customer),
            'orders' => $this->orderService->getOrdersForUser((int) $customer->getUserId()),
        ]);
    }

    public function showCreateForm(): void
    {
        $this->renderForm('Add Customer', [], [], null);
    }

    /**
     * Function/Method Name: storeCustomer
     * Purpose:      Create a customer account from the administrator's form.
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  Uses the same rules as self-registration, including a password.
     */
    public function storeCustomer(): void
    {
        $this->requireValidCsrfToken();
        $isEmailTaken = $this->userService->isEmailTaken((string) ($_POST['email'] ?? ''));
        $validationErrors = $this->userValidator->validateCustomerInput($_POST, $isEmailTaken, true);
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderForm('Add Customer', $_POST, $validationErrors, null);
            return;
        }

        $userData = $this->userValidator->toUserData($_POST);
        $newCustomerId = $this->userService->createUser($userData, User::ROLE_CUSTOMER);
        SessionService::setFlashData('highlightCustomerId', $newCustomerId);
        $this->redirectWithSuccess(buildUrl('customers'), 'Customer "' . $userData['name'] . '" was added.');
    }

    public function showEditForm(): void
    {
        $customer = $this->findRequestedCustomer($_GET);
        if ($customer !== null) {
            $this->renderForm('Edit Customer', $customer->getUserDetails(), [], $customer);
        }
    }

    /**
     * Function/Method Name: updateCustomer
     * Purpose:      Save changes to a customer's details.
     * Parameters:   none (reads $_POST)
     * Return Value: void
     * Description:  The password is not changed here; customers change it on their profile.
     */
    public function updateCustomer(): void
    {
        $this->requireValidCsrfToken();
        $customer = $this->findRequestedCustomer($_POST);
        if ($customer === null) {
            return;
        }

        $isEmailTaken = $this->userService->isEmailTaken((string) ($_POST['email'] ?? ''), $customer->getUserId());
        $validationErrors = $this->userValidator->validateCustomerInput($_POST, $isEmailTaken, false);
        if ($validationErrors !== []) {
            http_response_code(422);
            $this->renderForm('Edit Customer', $_POST, $validationErrors, $customer);
            return;
        }

        $previousDetails = $customer->getUserDetails();
        $userData = $this->userValidator->toUserData($_POST);
        $customer->setName($userData['name']);
        $customer->setEmail($userData['email']);
        $customer->setPhoneNumber($userData['phoneNumber']);
        $customer->setShippingAddress($userData['shippingAddress']);
        $this->userService->updateProfile($customer);

        SessionService::setFlashData('previousDetails', $previousDetails);
        ViewService::redirectTo(buildUrl('customer_edit_result', ['customerId' => $customer->getUserId()]));
    }

    public function showEditResult(): void
    {
        $customer = $this->findRequestedCustomer($_GET);
        if ($customer === null) {
            return;
        }

        ViewService::renderView('shared/update_result', [
            'pageTitle' => 'Customer Updated',
            'successMessage' => 'Customer #' . $customer->getUserId() . ' was updated at ' . formatDateTime($customer->getUpdatedAt()) . '.',
            'fieldLabels' => UserFieldLabels::forUser($customer),
            'previousDetails' => SessionService::pullFlashData('previousDetails'),
            'currentDetails' => $customer->getUserDetails(),
            'backLinks' => ['Edit again' => buildUrl('customer_edit', ['customerId' => $customer->getUserId()]), 'All customers' => buildUrl('customers')],
            'listTitle' => 'All Customers',
            'listPartial' => 'customers/partials/customer_table',
            'listData' => [
                'customers' => $this->userService->getAllCustomers(),
                'orderCounts' => $this->countOrdersPerCustomer(),
                'highlightCustomerId' => $customer->getUserId(),
            ],
        ]);
    }

    /**
     * Function/Method Name: confirmDelete
     * Purpose:      Show the whole customer record and ask "Are you sure?".
     * Parameters:   none (reads customerId from $_GET)
     * Return Value: void
     * Description:  A customer with orders shows why the account cannot be deleted.
     */
    public function confirmDelete(): void
    {
        $customer = $this->findRequestedCustomer($_GET);
        if ($customer === null) {
            return;
        }

        $orderCount = $this->userService->countOrdersForUser((int) $customer->getUserId());
        ViewService::renderView('shared/delete_confirm', [
            'pageTitle' => 'Delete Customer - Confirm',
            'entityLabel' => 'customer',
            'detailRows' => $this->toDetailRows($customer) + ['Orders' => (string) $orderCount],
            'blockedReason' => $orderCount > 0
                ? 'This customer has ' . $orderCount . ' order(s), so the account cannot be deleted. Order records must be kept.'
                : null,
            'deleteAction' => 'customer_delete',
            'hiddenFields' => ['customerId' => $customer->getUserId()],
            'cancelUrl' => buildUrl('customers'),
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    public function deleteCustomer(): void
    {
        $this->requireValidCsrfToken();
        $customer = $this->findRequestedCustomer($_POST);
        if ($customer === null) {
            return;
        }

        $countBefore = $this->userService->countCustomers();
        try {
            $this->userService->deleteCustomer((int) $customer->getUserId());
        } catch (BusinessRuleException $exception) {
            $this->redirectWithError(buildUrl('customer_delete_confirm', ['customerId' => $customer->getUserId()]), $exception->getMessage());
        }

        SessionService::setFlashData('deletedRecord', ['id' => $customer->getUserId(), 'rows' => $this->toDetailRows($customer), 'countBefore' => $countBefore]);
        ViewService::redirectTo(buildUrl('customer_delete_result'));
    }

    public function showDeleteResult(): void
    {
        $deletedRecord = SessionService::pullFlashData('deletedRecord');
        if ($deletedRecord === null) {
            ViewService::redirectTo(buildUrl('customers'));
        }

        ViewService::renderView('shared/delete_result', [
            'pageTitle' => 'Customer Deleted',
            'entityLabel' => 'Customer',
            'deletedRecord' => $deletedRecord,
            'isDeletionVerified' => $this->userService->findUserById((int) $deletedRecord['id']) === null,
            'countAfter' => $this->userService->countCustomers(),
            'listTitle' => 'All Customers',
            'listPartial' => 'customers/partials/customer_table',
            'listData' => ['customers' => $this->userService->getAllCustomers(), 'orderCounts' => $this->countOrdersPerCustomer()],
        ]);
    }

    private function renderForm(string $pageTitle, array $formInput, array $validationErrors, ?Customer $customer): void
    {
        ViewService::renderView('customers/form', [
            'pageTitle' => $pageTitle,
            'formInput' => $formInput,
            'validationErrors' => $validationErrors,
            'customer' => $customer,
            'csrfToken' => SessionService::getCsrfToken(),
        ]);
    }

    private function toDetailRows(Customer $customer): array
    {
        return [
            'Customer ID' => (string) $customer->getUserId(),
            'Name' => $customer->getName(),
            'Email' => $customer->getEmail(),
            'Phone Number' => $customer->getPhoneNumber(),
            'Shipping Address' => $customer->getShippingAddress(),
            'Registered' => formatDateTime($customer->getCreatedAt()),
        ];
    }

    private function countOrdersPerCustomer(): array
    {
        $orderCounts = [];
        foreach ($this->orderService->getAllOrders() as $order) {
            $orderCounts[$order->getUserId()] = ($orderCounts[$order->getUserId()] ?? 0) + 1;
        }

        return $orderCounts;
    }

    /**
     * Function/Method Name: findRequestedCustomer
     * Purpose:      Load the customer whose ID was sent, or show "Not found".
     * Parameters:   array $requestInput - $_GET or $_POST
     * Return Value: ?Customer - null after the "Not found" page was shown
     * Description:  An administrator's ID is also "Not found" here, so admin accounts
     *               can never be edited or deleted through Manage Customers.
     */
    private function findRequestedCustomer(array $requestInput): ?Customer
    {
        $customerId = $this->readIdFromRequest($requestInput, 'customerId');
        $user = $customerId === null ? null : $this->userService->findUserById($customerId);
        if (!$user instanceof Customer) {
            $this->showNotFound(
                'There is no customer with ID "' . ($requestInput['customerId'] ?? '') . '". It may have been deleted already.',
                ['Search customers' => buildUrl('customers')]
            );
            return null;
        }

        return $user;
    }
}

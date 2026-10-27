<?php
/**
 * View:     layout/header.php
 * Purpose:  Opening HTML, the role-based top navigation, flash messages and the page hero.
 * Data:     string $pageTitle
 *           ?User  $currentUser, int $cartItemCount, string $currentAction, string $currentSection (layout data)
 */
$isAdministrator = $currentUser?->isAdministrator() ?? false;
$isCustomer = $currentUser instanceof Customer;

// menu label => [route, section that highlights it]
$navigationLinks = match (true) {
    $isAdministrator => [
        'Dashboard' => ['dashboard', 'dashboard'],
        'Products' => ['list', 'products'],
        'Inventory' => ['inventory', 'inventory'],
        'Categories' => ['categories', 'categories'],
        'Orders' => ['admin_orders', 'admin_orders'],
        'Payments' => ['payments', 'payments'],
        'Customers' => ['customers', 'customers'],
    ],
    $isCustomer => ['Shop' => ['shop', 'shop'], 'My Orders' => ['orders', 'orders']],
    default => ['Shop' => ['shop', 'shop']],
};

// Lab 3 & 4 product pages keep their own tabs inside the Products section
$productTabs = ['list' => 'All Products', 'create' => 'Add Product', 'search' => 'Search', 'edit_search' => 'Edit', 'delete_search' => 'Delete'];

$sectionLabels = [
    'shop' => 'Shop', 'cart' => 'Shopping Cart', 'orders' => 'My Orders', 'account' => 'My Account',
    'dashboard' => 'Overview', 'products' => 'Manage Products', 'inventory' => 'Manage Inventory',
    'categories' => 'Manage Product Categories', 'admin_orders' => 'Manage Orders', 'payments' => 'Payments',
    'customers' => 'Manage Customers',
];
$heroEyebrow = ($isAdministrator ? 'Administrator' : ($isCustomer ? 'Customer' : 'Welcome')) . ' · ' . ($sectionLabels[$currentSection] ?? APP_SHORT_NAME);

$flashSuccess = SessionService::pullFlashData('flashSuccess');
$flashError = SessionService::pullFlashData('flashError');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($pageTitle) ?> | <?= escapeHtml(APP_SHORT_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="top-nav">
    <div class="top-nav__inner">
        <a class="brand" href="<?= escapeHtml(buildUrl($isAdministrator ? 'dashboard' : 'shop')) ?>">
            <span class="brand__mark" aria-hidden="true">LM</span>
            <span class="brand__name"><?= escapeHtml(APP_NAME) ?></span>
        </a>
        <nav class="main-nav" aria-label="Main menu">
            <?php foreach ($navigationLinks as $linkLabel => [$linkAction, $linkSection]): ?>
                <a href="<?= escapeHtml(buildUrl($linkAction)) ?>"
                   class="<?= $currentSection === $linkSection ? 'is-active' : '' ?>"><?= escapeHtml($linkLabel) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="top-nav__actions">
            <?php if ($currentUser === null): ?>
                <a class="button button--ghost" href="<?= escapeHtml(buildUrl('login')) ?>">Log in</a>
                <a class="button button--primary" href="<?= escapeHtml(buildUrl('register')) ?>">Register</a>
            <?php else: ?>
                <?php if ($isCustomer): ?>
                    <a class="button button--primary <?= $currentSection === 'cart' ? 'is-active' : '' ?>" href="<?= escapeHtml(buildUrl('cart')) ?>">
                        Cart <span class="cart-count"><?= $cartItemCount ?></span>
                    </a>
                <?php endif; ?>
                <a class="account-link <?= $currentSection === 'account' ? 'is-active' : '' ?>" href="<?= escapeHtml(buildUrl('profile')) ?>"
                   title="My profile"><?= escapeHtml(strtok($currentUser->getName(), ' ')) ?></a>
                <form method="post" action="<?= escapeHtml(buildUrl('logout')) ?>" class="inline-form">
                    <input type="hidden" name="csrfToken" value="<?= escapeHtml(SessionService::getCsrfToken()) ?>">
                    <button type="submit" class="button button--ghost">Log out</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="announcement">
    <span class="announcement__badge">NEW</span>
    <?php if ($isAdministrator): ?>
        <span>Orders, payments, customers and categories can now be managed from the menu above</span>
    <?php else: ?>
        <span>Order local groceries online &mdash; pay by Online Banking, E-Wallet or Cash on Delivery</span>
    <?php endif; ?>
</div>

<section class="hero">
    <div class="hero__inner">
        <p class="hero__eyebrow"><?= escapeHtml($heroEyebrow) ?></p>
        <h1 class="hero__title"><?= escapeHtml($pageTitle) ?></h1>
    </div>
</section>

<main class="page">
    <?php if ($currentSection === 'products'): ?>
        <nav class="sub-nav" aria-label="Product pages">
            <?php foreach ($productTabs as $tabAction => $tabLabel): ?>
                <a href="<?= escapeHtml(buildUrl($tabAction)) ?>" class="<?= $currentAction === $tabAction ? 'is-active' : '' ?>"><?= escapeHtml($tabLabel) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if ($flashSuccess): ?>
        <div class="alert alert--success" role="status" data-auto-dismiss><?= escapeHtml($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert--error" role="alert"><?= escapeHtml($flashError) ?></div>
    <?php endif; ?>

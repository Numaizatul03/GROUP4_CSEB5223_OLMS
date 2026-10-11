<?php
/**
 * View:     layout/header.php
 * Purpose:  Opening HTML, top navigation, announcement banner and the gradient page hero.
 * Data:     string $pageTitle
 */
$currentAction = (string) ($_GET['action'] ?? 'list');
$navigationLinks = [
    'list' => 'All Products',
    'search' => 'Search',
    'edit_search' => 'Edit',
    'delete_search' => 'Delete',
];
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
        <a class="brand" href="<?= escapeHtml(buildUrl('list')) ?>">
            <span class="brand__mark" aria-hidden="true">LM</span>
            <span class="brand__name"><?= escapeHtml(APP_NAME) ?></span>
        </a>
        <nav class="main-nav" aria-label="Product module">
            <?php foreach ($navigationLinks as $linkAction => $linkLabel): ?>
                <a href="<?= escapeHtml(buildUrl($linkAction)) ?>"
                   class="<?= $currentAction === $linkAction ? 'is-active' : '' ?>"><?= escapeHtml($linkLabel) ?></a>
            <?php endforeach; ?>
        </nav>
        <a class="button button--primary top-nav__action <?= $currentAction === 'create' ? 'is-active' : '' ?>"
           href="<?= escapeHtml(buildUrl('create')) ?>">+ Add Product</a>
    </div>
</header>

<div class="announcement">
    <span class="announcement__badge">NEW</span>
    <span>Product module is live &mdash; create, search, edit and delete products</span>
</div>

<section class="hero">
    <div class="hero__inner">
        <p class="hero__eyebrow">Administrator &middot; Manage Products</p>
        <h1 class="hero__title"><?= escapeHtml($pageTitle) ?></h1>
    </div>
</section>

<main class="page">

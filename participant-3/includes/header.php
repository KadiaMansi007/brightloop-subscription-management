<?php
$pageTitle = $pageTitle ?? 'Subscription Management System';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="/Subscription_Management_System/assets/css/style.css">
</head><body><header class="site-header"><a href="/Subscription_Management_System/admin/dashboard.php">Subscription Management</a>
<nav><a href="/Subscription_Management_System/customer/dashboard.php">Customer</a> <a href="/Subscription_Management_System/admin/dashboard.php">Admin</a></nav></header><main class="container">

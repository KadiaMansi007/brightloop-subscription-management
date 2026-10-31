<?php
require __DIR__ . '/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); if (current_user()) audit('logout'); logout_user(); }
header('Location: ' . url('login.php')); exit;

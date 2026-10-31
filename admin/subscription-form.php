<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';
// Create Subscription Form
?>
<h2>Create Subscription</h2>
<form method="POST">
    <input type="number" name="customer_id" placeholder="Customer ID" required><br>
    <input type="number" name="plan_id" placeholder="Plan ID" required><br>
    <button type="submit">Create Draft Subscription</button>
</form>

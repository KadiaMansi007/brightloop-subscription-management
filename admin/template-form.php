<?php
require_once '../includes/bootstrap.php';
require_once '../includes/auth.php';
// Create/Edit Quotation Template Form
?>
<h2>Create Quotation Template</h2>
<form method="POST">
    <input type="text" name="name" placeholder="Template Name" required><br>
    <input type="number" name="validity_days" placeholder="Validity (Days)" required><br>
    <button type="submit">Save Template</button>
</form>

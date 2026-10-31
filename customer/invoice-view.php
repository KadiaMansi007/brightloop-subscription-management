<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['customer']); $cid = (int)$u['id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        record_payment($id, (float)($_POST['amount'] ?? 0), $_POST['method'] ?? '', date('Y-m-d'), 'Paid online (demo)', $cid, $cid);
        audit('customer_payment', 'invoice', $id); flash('success', 'Payment successful. Thank you!');
    } catch (Throwable $x) { flash('error', $x->getMessage()); }
    redirect('customer/invoice-view.php?id=' . $id);
}
$inv = qrow("SELECT i.*, u.name customer_name, u.email customer_email, s.subscription_no sub_no FROM invoices i JOIN users u ON u.id=i.customer_id JOIN subscriptions s ON s.id=i.subscription_id WHERE i.id=? AND i.customer_id=? AND i.status<>'draft'", [$id, $cid]);
if (!$inv) { http_response_code(404); exit('Invoice not found.'); }
$items = qall('SELECT * FROM invoice_items WHERE invoice_id=?', [$id]); $pays = qall('SELECT * FROM payments WHERE invoice_id=? ORDER BY id', [$id]);
$balance = round((float)$inv['total'] - (float)$inv['amount_paid'], 2);
$pageTitle = 'Invoice ' . $inv['invoice_no']; include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/invoice_partial.php'; ?>
<div class="card"><button class="btn small" type="button" onclick="window.print()">Print</button> <a class="btn small" href="invoices.php">Back</a>
<?php if ($inv['status'] === 'confirmed' && $balance > 0): ?>
<h3>Pay now (demo – no real money moves)</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
<label>Amount <input type="number" step="0.01" min="0.01" max="<?= e($balance) ?>" name="amount" value="<?= e($balance) ?>" required></label>
<label>Method <select name="method"><option value="upi">UPI</option><option value="card">Card</option><option value="bank_transfer">Bank transfer</option></select></label>
<button class="btn">Pay <?= money($balance) ?></button></form><?php endif; ?></div>
<?php include __DIR__ . '/../includes/footer.php';

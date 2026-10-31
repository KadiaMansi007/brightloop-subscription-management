<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin', 'staff']);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $a = $_POST['action'] ?? '';
    try {
        if ($a === 'confirm') { set_invoice_status($id, 'confirmed'); flash('success', 'Invoice confirmed and customer notified.'); }
        elseif ($a === 'cancel') { set_invoice_status($id, 'cancelled'); flash('success', 'Invoice cancelled.'); }
        elseif ($a === 'send') {
            $c = qrow('SELECT i.invoice_no, i.total, u.email FROM invoices i JOIN users u ON u.id=i.customer_id WHERE i.id=?', [$id]);
            if ($c) { send_mail($c['email'], 'Invoice ' . $c['invoice_no'], 'Amount due: ' . $c['total']); db()->prepare('UPDATE invoices SET sent_at=NOW() WHERE id=?')->execute([$id]); flash('success', 'Invoice emailed (see uploads/mail.log on XAMPP).'); }
        } elseif ($a === 'pay') {
            record_payment($id, (float)($_POST['amount'] ?? 0), $_POST['method'] ?? '', $_POST['payment_date'] ?: date('Y-m-d'), trim($_POST['note'] ?? ''), (int)$u['id']);
            audit('record_payment', 'invoice', $id); flash('success', 'Payment recorded.');
        }
    } catch (Throwable $x) { flash('error', $x->getMessage()); }
    redirect('admin/invoice-view.php?id=' . $id);
}
$inv = qrow('SELECT i.*, u.name customer_name, u.email customer_email, s.subscription_no sub_no FROM invoices i JOIN users u ON u.id=i.customer_id JOIN subscriptions s ON s.id=i.subscription_id WHERE i.id=?', [$id]);
if (!$inv) { http_response_code(404); exit('Invoice not found.'); }
$items = qall('SELECT * FROM invoice_items WHERE invoice_id=?', [$id]); $pays = qall('SELECT * FROM payments WHERE invoice_id=? ORDER BY id', [$id]);
$balance = round((float)$inv['total'] - (float)$inv['amount_paid'], 2);
$pageTitle = 'Invoice ' . $inv['invoice_no']; include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/invoice_partial.php'; ?>
<div class="card"><h3>Actions</h3>
<?php if ($inv['status'] === 'draft') echo act_form($id, 'confirm', 'Confirm invoice'); ?>
<?php if (in_array($inv['status'], ['draft', 'confirmed'], true) && (float)$inv['amount_paid'] == 0) echo act_form($id, 'cancel', 'Cancel invoice'); ?>
<?php if ($inv['status'] !== 'draft') echo act_form($id, 'send', 'Email to customer'); ?>
<button class="btn small" type="button" onclick="window.print()">Print</button> <a class="btn small" href="invoices.php">Back</a>
<?php if ($inv['status'] === 'confirmed' && $balance > 0): ?>
<h3>Record payment</h3><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="pay"><input type="hidden" name="id" value="<?= $id ?>">
<label>Amount <input type="number" step="0.01" min="0.01" max="<?= e($balance) ?>" name="amount" value="<?= e($balance) ?>" required></label>
<label>Method <select name="method"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="card">Card</option><option value="upi">UPI</option><option value="other">Other</option></select></label>
<label>Date <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"></label><label>Note <input name="note" maxlength="255"></label><button class="btn">Record payment</button></form>
<?php endif; ?></div>
<?php include __DIR__ . '/../includes/footer.php';

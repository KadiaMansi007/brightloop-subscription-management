<?php /* expects $inv (with customer_name,customer_email,sub_no), $items, $pays */ ?>
<div class="card" id="invoice">
  <h2>Invoice <?= e($inv['invoice_no']) ?> <?= status_badge($inv['status']) ?></h2>
  <p>Customer: <strong><?= e($inv['customer_name']) ?></strong> (<?= e($inv['customer_email']) ?>)<br>
     Subscription: <?= e($inv['sub_no']) ?> · Period: <?= e($inv['period_start']) ?> to <?= e($inv['period_end']) ?><br>
     Invoice date: <?= e($inv['invoice_date']) ?> · Due: <?= e($inv['due_date']) ?></p>
  <table><tr><th>Description</th><th>Qty</th><th>Unit</th><th>Discount</th><th>Tax %</th><th>Tax</th><th>Amount</th></tr>
  <?php foreach ($items as $it): ?>
    <tr><td><?= e($it['description']) ?></td><td><?= (int)$it['quantity'] ?></td><td><?= money($it['unit_price']) ?></td><td><?= money($it['discount_amount']) ?></td>
        <td><?= e($it['tax_percent']) ?></td><td><?= money($it['tax_amount']) ?></td><td><?= money($it['line_amount']) ?></td></tr>
  <?php endforeach; ?></table>
  <p>Subtotal <?= money($inv['subtotal']) ?> · Discount −<?= money($inv['discount_total']) ?> · Tax <?= money($inv['tax_total']) ?><br>
     <strong>Total <?= money($inv['total']) ?></strong> · Paid <?= money($inv['amount_paid']) ?> · <strong>Balance <?= money($inv['total'] - $inv['amount_paid']) ?></strong></p>
  <?php if ($pays): ?><h3>Payments</h3><table><tr><th>Date</th><th>Method</th><th>Amount</th><th>Note</th></tr>
  <?php foreach ($pays as $p): ?><tr><td><?= e($p['payment_date']) ?></td><td><?= e($p['method']) ?></td><td><?= money($p['amount']) ?></td><td><?= e($p['note']) ?></td></tr><?php endforeach; ?></table><?php endif; ?>
</div>

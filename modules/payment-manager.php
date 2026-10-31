<?php
declare(strict_types=1);
function record_manual_payment(PDO $pdo, int $invoiceId, int $customerId, float $amount, string $method, ?string $reference, int $recordedBy): void {
    if ($amount<=0) throw new InvalidArgumentException('Payment amount must be greater than zero.');
    $pdo->beginTransaction();
    try {
        $q=$pdo->prepare("SELECT id,total_amount,paid_amount FROM invoices WHERE id=? AND customer_id=? FOR UPDATE");
        $q->execute([$invoiceId,$customerId]); $invoice=$q->fetch();
        if (!$invoice) throw new RuntimeException('Invoice not found.');
        $balance=max(0,(float)$invoice['total_amount']-(float)$invoice['paid_amount']);
        if ($amount>$balance+0.00001) throw new RuntimeException('Payment exceeds invoice balance.');
        $ins=$pdo->prepare("INSERT INTO payments(invoice_id,customer_id,amount,method,status,reference,recorded_by) VALUES(?,?,?,?, 'completed',?,?)");
        $ins->execute([$invoiceId,$customerId,$amount,$method,$reference,$recordedBy]);
        $newPaid=(float)$invoice['paid_amount']+$amount;
        $status=$newPaid+0.00001 >= (float)$invoice['total_amount'] ? 'paid' : 'partially_paid';
        $up=$pdo->prepare("UPDATE invoices SET paid_amount=?,status=? WHERE id=?");
        $up->execute([$newPaid,$status,$invoiceId]);
        $pdo->commit();
    } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
}

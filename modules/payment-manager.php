<?php
function record_manual_payment(PDO $pdo,int $invoiceId,float $amount,string $method,?string $reference,int $recordedBy):void {
 if($amount<=0) throw new InvalidArgumentException('Payment amount must be greater than zero.');
 $pdo->beginTransaction();
 try {
  $q=$pdo->prepare("SELECT id,customer_id,total,amount_paid FROM invoices WHERE id=? FOR UPDATE");$q->execute([$invoiceId]);$inv=$q->fetch();
  if(!$inv)throw new RuntimeException('Invoice not found.');
  $balance=max(0,(float)$inv['total']-(float)$inv['amount_paid']);
  if($amount>$balance+0.00001)throw new RuntimeException('Payment exceeds invoice balance.');
  $ins=$pdo->prepare("INSERT INTO payments(invoice_id,customer_id,method,amount,payment_date,note,recorded_by,status,reference) VALUES(?,?,?,?,CURDATE(),?,?,?,?)");
  $ins->execute([$invoiceId,$inv['customer_id'],$method,$amount,$reference,$recordedBy,'completed',$reference]);
  $paid=(float)$inv['amount_paid']+$amount;$status=$paid+0.00001 >= (float)$inv['total']?'paid':'confirmed';
  $up=$pdo->prepare("UPDATE invoices SET amount_paid=?,status=? WHERE id=?");$up->execute([$paid,$status,$invoiceId]);
  $pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

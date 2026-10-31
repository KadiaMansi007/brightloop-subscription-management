<?php
require_once __DIR__.'/../includes/auth.php'; require_staff();
require_once __DIR__.'/../modules/payment-manager.php';
$pageTitle='Record Payment'; require_once __DIR__.'/../includes/header.php';
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{record_manual_payment($pdo,(int)$_POST['invoice_id'],(int)$_POST['customer_id'],(float)$_POST['amount'],$_POST['method']??'manual',trim($_POST['reference']??'')?:null,current_user_id());$message='Payment recorded successfully.';}
 catch(Throwable $e){$message='Could not record payment: '.h($e->getMessage());}
}
?><h1>Record manual/demo payment</h1><p><?=h($message)?></p>
<form method="post" class="panel"><label>Invoice ID <input type="number" name="invoice_id" min="1" required></label>
<label>Customer ID <input type="number" name="customer_id" min="1" required></label>
<label>Amount <input type="number" step=".01" min=".01" name="amount" required></label>
<label>Method <select name="method"><option value="cash">Cash</option><option value="bank_transfer">Bank transfer</option><option value="upi_demo">UPI demo</option><option value="manual">Manual</option></select></label>
<label>Reference (optional) <input name="reference" maxlength="100"></label><button>Record payment</button></form>
<p class="muted">Demo/manual record only. This page does not charge a card or initiate a UPI payment.</p>
<?php require_once __DIR__.'/../includes/footer.php';?>

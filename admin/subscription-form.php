<?php
require __DIR__.'/../includes/bootstrap.php'; require_role(['admin','staff']);
$customers=db()->query("SELECT id,name,email FROM users WHERE role='customer' AND status='active' ORDER BY name")->fetchAll();
$plans=db()->query("SELECT id,name,price FROM plans WHERE status='active' ORDER BY name")->fetchAll(); $message='';
if($_SERVER['REQUEST_METHOD']==='POST'){ csrf_check(); try {
 require_once __DIR__.'/../modules/subscriptions.php';
 $id=create_subscription(db(),['customer_id'=>(int)$_POST['customer_id'],'plan_id'=>(int)$_POST['plan_id'],'start_date'=>$_POST['start_date'],'expiry_date'=>$_POST['expiry_date']?:null]);
 flash('success','Draft subscription created.'); redirect('admin/subscriptions.php');
} catch(Throwable $e){$message=$e->getMessage();}}
$pageTitle='Create subscription'; include __DIR__.'/../includes/header.php'; ?>
<h1>Create draft subscription</h1><?php if($message):?><div class="alert error"><?=e($message)?></div><?php endif?>
<form method="post" class="panel"><?=csrf_field()?><label>Customer<select name="customer_id" required><?php foreach($customers as $c):?><option value="<?=e($c['id'])?>"><?=e($c['name'].' — '.$c['email'])?></option><?php endforeach?></select></label>
<label>Plan<select name="plan_id" required><?php foreach($plans as $p):?><option value="<?=e($p['id'])?>"><?=e($p['name'].' — ₹'.$p['price'])?></option><?php endforeach?></select></label>
<label>Start date<input type="date" name="start_date" value="<?=e(date('Y-m-d'))?>" required></label><label>Expiry date<input type="date" name="expiry_date"></label><button class="btn">Create</button></form>
<?php include __DIR__.'/../includes/footer.php'; ?>
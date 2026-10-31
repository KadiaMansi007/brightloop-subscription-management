<?php
require_once __DIR__.'/../includes/permissions.php';$cid=customer_id_for_session();
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=$_POST['request_type']??'';$sid=(int)($_POST['subscription_id']??0);$note=trim($_POST['note']??'');
 if(!in_array($type,['pause','renewal','cancellation'],true))$message='Invalid request type.';
 else{
  $q=$pdo->prepare("SELECT id FROM subscriptions WHERE id=? AND customer_id=?");$q->execute([$sid,$cid]);
  if(!$q->fetch())$message='Subscription not found.';
  else{$q=$pdo->prepare("INSERT INTO subscription_requests(customer_id,subscription_id,request_type,note,status) VALUES(?,?,?,?, 'pending')");$q->execute([$cid,$sid,$type,$note]);$message='Request submitted for admin review.';}
 }
}
$q=$pdo->prepare("SELECT id,product_name FROM subscriptions WHERE customer_id=?");$q->execute([$cid]);$subs=$q->fetchAll();
$pageTitle='Subscription Requests';require_once __DIR__.'/../includes/header.php';
?><h1>Pause / renewal / cancellation request</h1><p><?=h($message)?></p><form method="post" class="panel"><label>Subscription <select name="subscription_id" required><?php foreach($subs as $s):?><option value="<?=h($s['id'])?>"><?=h($s['product_name'])?></option><?php endforeach?></select></label><label>Request <select name="request_type"><option value="pause">Pause</option><option value="renewal">Renewal</option><option value="cancellation">Cancellation</option></select></label><label>Note <textarea name="note"></textarea></label><button>Submit request</button></form>
<?php require_once __DIR__.'/../includes/footer.php';?>

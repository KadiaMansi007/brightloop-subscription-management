<?php
require __DIR__.'/../includes/bootstrap.php';$u=require_role(['customer']);$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){csrf_check();$sid=(int)($_POST['subscription_id']??0);$type=$_POST['request_type']??'';$note=trim($_POST['note']??'');
 if(!in_array($type,['pause','renewal','cancellation'],true))$message='Invalid request type.';
 else{$q=db()->prepare("SELECT id FROM subscriptions WHERE id=? AND customer_id=?");$q->execute([$sid,$u['id']]);if(!$q->fetch())$message='Subscription not found.';
 else{$q=db()->prepare("INSERT INTO subscription_requests(customer_id,subscription_id,request_type,note) VALUES(?,?,?,?)");$q->execute([$u['id'],$sid,$type,$note]);$message='Request submitted for review.';}}}
$q=db()->prepare("SELECT s.id,p.name FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.customer_id=?");$q->execute([$u['id']]);$subs=$q->fetchAll();
$pageTitle='Subscription requests';include __DIR__.'/../includes/header.php';?>
<h1>Pause / renewal / cancellation request</h1><?php if($message):?><div class="alert"><?=e($message)?></div><?php endif?>
<form method="post" class="panel"><?=csrf_field()?><label>Subscription<select name="subscription_id" required><?php foreach($subs as $s):?><option value="<?=e($s['id'])?>"><?=e($s['name'].' (#'.$s['id'].')')?></option><?php endforeach?></select></label><label>Request<select name="request_type"><option value="pause">Pause</option><option value="renewal">Renewal</option><option value="cancellation">Cancellation</option></select></label><label>Note<textarea name="note"></textarea></label><button class="btn">Submit request</button></form>
<?php include __DIR__.'/../includes/footer.php';?>
<?php
require_once __DIR__.'/../includes/auth.php'; require_admin();
require_once __DIR__.'/../includes/header.php';
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $code=strtoupper(trim($_POST['code']??'')); $type=$_POST['discount_type']??'percent';
  $value=(float)($_POST['discount_value']??0); $min=(float)($_POST['min_purchase']??0);
  if(!preg_match('/^[A-Z0-9_-]{3,40}$/',$code)||!in_array($type,['percent','fixed'],true)||$value<=0||$min<0||($type==='percent'&&$value>100)) $notice='Please enter valid discount details.';
  else { $q=$pdo->prepare("INSERT INTO discounts(code,discount_type,discount_value,min_purchase,starts_at,ends_at,usage_limit,is_active) VALUES(?,?,?,?,?,?,?,1)");
    $q->execute([$code,$type,$value,$min,$_POST['starts_at']?:null,$_POST['ends_at']?:null,$_POST['usage_limit']!==''?(int)$_POST['usage_limit']:null]); $notice='Discount created.'; }
}
$rows=$pdo->query("SELECT * FROM discounts ORDER BY id DESC")->fetchAll();
?>
<h1>Discount management</h1><p><?=h($notice)?></p>
<form method="post" class="panel"><label>Coupon code <input name="code" required></label>
<label>Type <select name="discount_type"><option value="percent">Percentage</option><option value="fixed">Fixed amount</option></select></label>
<label>Value <input type="number" step=".01" min=".01" name="discount_value" required></label>
<label>Minimum purchase <input type="number" step=".01" min="0" name="min_purchase" value="0"></label>
<label>Starts <input type="datetime-local" name="starts_at"></label><label>Ends <input type="datetime-local" name="ends_at"></label>
<label>Usage limit <input type="number" min="1" name="usage_limit"></label><button>Create discount</button></form>
<table><tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Active</th></tr>
<?php foreach($rows as $r):?><tr><td><?=h($r['code'])?></td><td><?=h($r['discount_type'])?></td><td><?=h($r['discount_value'])?></td><td><?=h($r['used_count'])?></td><td><?=h($r['is_active'])?></td></tr><?php endforeach?></table>
<?php require_once __DIR__.'/../includes/footer.php'; ?>

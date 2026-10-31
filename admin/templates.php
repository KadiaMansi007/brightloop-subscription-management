<?php
require __DIR__.'/../includes/bootstrap.php';require_role(['admin']);
$rows=db()->query("SELECT qt.*,p.name AS plan_name FROM quotation_templates qt LEFT JOIN plans p ON p.id=qt.plan_id ORDER BY qt.id DESC")->fetchAll();
$pageTitle='Quotation templates';include __DIR__.'/../includes/header.php';?>
<h1>Quotation templates</h1><p><a class="btn" href="<?=e(url('admin/template-form.php'))?>">Create template</a></p>
<div class="table-wrap"><table><tr><th>Name</th><th>Plan</th><th>Validity (days)</th><th>Created</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['name'])?></td><td><?=e($r['plan_name']??'—')?></td><td><?=e($r['validity_days'])?></td><td><?=e($r['created_at'])?></td></tr><?php endforeach?></table></div>
<?php include __DIR__.'/../includes/footer.php';?>
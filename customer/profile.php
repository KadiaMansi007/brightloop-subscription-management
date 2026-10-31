<?php
require __DIR__.'/../includes/bootstrap.php';$u=require_role(['customer']);
$pageTitle='My profile';include __DIR__.'/../includes/header.php';?>
<h1>My profile</h1><section class="panel"><p><strong>Name:</strong> <?=e($u['name'])?></p><p><strong>Email:</strong> <?=e($u['email'])?></p><p><strong>Role:</strong> <?=e($u['role'])?></p></section>
<?php include __DIR__.'/../includes/footer.php';?>
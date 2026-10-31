<?php
require_once __DIR__.'/../includes/permissions.php';$cid=customer_id_for_session();
$q=$pdo->prepare("SELECT id,name,email FROM users WHERE id=?");$q->execute([$cid]);$u=$q->fetch();
$pageTitle='My Profile';require_once __DIR__.'/../includes/header.php';
?><h1>My profile</h1><?php if($u):?><p>Name: <?=h($u['name'])?></p><p>Email: <?=h($u['email'])?></p><?php else:?><p>Profile not found.</p><?php endif?>
<?php require_once __DIR__.'/../includes/footer.php';?>

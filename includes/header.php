<?php $u = current_user(); $pageTitle = $pageTitle ?? APP_NAME; ?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head><body>
<?php if ($u): ?>
<header class="topbar">
  <a class="brand" href="<?= e(url(home_for($u['role']))) ?>">Brightloop</a>
  <nav>
    <?php foreach (nav_for($u['role']) as $label => $path): ?>
      <a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <form method="post" action="<?= e(url('logout.php')) ?>" class="logout">
    <?= csrf_field() ?><span><?= e($u['name']) ?> (<?= e($u['role']) ?>)</span><button class="btn small">Logout</button>
  </form>
</header>
<?php endif; ?>
<main class="container">
<?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>

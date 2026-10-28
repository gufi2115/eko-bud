<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
if (!admin_exists()) redirect('/admin/install.php');
if (admin_logged_in()) redirect('/admin/');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $err = admin_attempt((string)($_POST['login'] ?? ''), (string)($_POST['password'] ?? '')) ?? '';
    if ($err === '') redirect('/admin/');
}
$f = flash();
?><!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>Logowanie – panel</title><link rel="stylesheet" href="/admin/assets/admin.css"></head>
<body class="login"><main class="box">
<h1>Panel administratora</h1>
<?php if ($f): ?><p class="flash ok"><?= e($f['msg']) ?></p><?php endif; ?>
<?php if ($err): ?><p class="flash err" role="alert"><?= e($err) ?></p><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Login<input name="login" required autocomplete="username" autofocus></label>
<label>Hasło<input name="password" type="password" required autocomplete="current-password"></label>
<button class="b">Zaloguj się</button></form></main></body></html>

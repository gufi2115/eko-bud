<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_admin();
define('ADMIN', true);

$pages = ['dashboard' => 'Start', 'settings' => 'Dane firmy i SEO', 'services' => 'Usługi', 'gallery' => 'Galeria',
          'advantages' => 'Dlaczego my', 'reviews' => 'Opinie', 'messages' => 'Wiadomości', 'account' => 'Hasło i kopia zapasowa'];
$p = (string)($_GET['p'] ?? 'dashboard');
if (!isset($pages[$p])) $p = 'dashboard';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_check();

function move_row(string $table, int $id, string $dir): void
{
    $ids = array_map('intval', db()->query("SELECT id FROM $table ORDER BY position,id")->fetchAll(PDO::FETCH_COLUMN));
    $i = array_search($id, $ids, true);
    if ($i === false) return;
    $j = $dir === 'up' ? $i - 1 : $i + 1;
    if (!isset($ids[$j])) return;
    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
    $st = db()->prepare("UPDATE $table SET position=? WHERE id=?");
    foreach ($ids as $n => $x) $st->execute([$n + 1, $x]);
}
function back(string $p, string $msg, string $type = 'ok'): never { flash($msg, $type); redirect('/admin/?p=' . $p); }

ob_start();
require __DIR__ . '/pages/' . $p . '.php';
$content = ob_get_clean();
$unread = (int)db()->query('SELECT COUNT(*) FROM messages WHERE is_read=0')->fetchColumn();
$f = flash();
?><!doctype html><html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title><?= e($pages[$p]) ?> – panel</title>
<link rel="stylesheet" href="/admin/assets/admin.css"><script src="/admin/assets/admin.js" defer></script></head>
<body>
<header class="ah"><strong>Panel</strong>
<nav><?php foreach ($pages as $k => $t): ?><a href="/admin/?p=<?= $k ?>"<?= $k === $p ? ' class="on"' : '' ?>><?= e($t) ?><?= $k === 'messages' && $unread ? " ($unread)" : '' ?></a><?php endforeach; ?>
<a href="/" target="_blank">Zobacz stronę ↗</a></nav>
<form method="post" action="/admin/logout.php"><?= csrf_field() ?><button class="b sm">Wyloguj</button></form></header>
<main class="am"><h1><?= e($pages[$p]) ?></h1>
<?php if ($f): ?><p class="flash <?= e($f['type']) ?>" role="status"><?= $f['msg'] /* msg budowane z e() po stronie wywołującego */ ?></p><?php endif; ?>
<?= $content ?></main></body></html>

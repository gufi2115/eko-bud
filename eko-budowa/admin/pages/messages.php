<?php defined('ADMIN') or exit;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    match ((string)($_POST['act'] ?? '')) {
        'read' => db()->prepare('UPDATE messages SET is_read=1 WHERE id=?')->execute([$id]),
        'unread' => db()->prepare('UPDATE messages SET is_read=0 WHERE id=?')->execute([$id]),
        'delete' => db()->prepare('DELETE FROM messages WHERE id=?')->execute([$id]),
        default => null,
    };
    back('messages', 'Gotowe.');
}
$rows = db()->query('SELECT * FROM messages ORDER BY id DESC LIMIT 200')->fetchAll();
if (!$rows) echo '<p class="muted">Brak wiadomości.</p>';
foreach ($rows as $m): $tel = preg_replace('/[^0-9+]/', '', $m['phone']); ?>
<form method="post" class="card msg <?= $m['is_read'] ? '' : 'new' ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
<strong><?= e($m['name']) ?></strong> <span class="muted">• <?= e($m['created_at']) ?> • <?= e($m['type']) ?></span>
<p><?php if ($m['phone']): ?>☎ <a href="tel:<?= e($tel) ?>"><?= e($m['phone']) ?></a> <?php endif; ?>
<?php if ($m['email']): ?>✉ <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?php endif; ?></p>
<pre><?= e($m['message']) ?></pre>
<div class="row"><button class="b sm gr" name="act" value="<?= $m['is_read'] ? 'unread' : 'read' ?>"><?= $m['is_read'] ? 'Oznacz jako nieprzeczytaną' : 'Oznacz jako przeczytaną' ?></button>
<button class="b sm red" name="act" value="delete" data-confirm="Usunąć wiadomość?">Usuń</button></div></form>
<?php endforeach; ?>

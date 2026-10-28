<?php defined('ADMIN') or exit;
$n = fn(string $t): int => (int)db()->query("SELECT COUNT(*) FROM $t")->fetchColumn();
?>
<div class="card"><p>Witaj! Tu zmienisz treść strony bez dotykania kodu.</p>
<p>Usługi: <b><?= $n('services') ?></b> • Zdjęcia: <b><?= $n('gallery') ?></b> • Nowe wiadomości: <b><?= (int)db()->query('SELECT COUNT(*) FROM messages WHERE is_read=0')->fetchColumn() ?></b></p>
<div class="row"><a class="b" href="/admin/?p=gallery">📷 Dodaj zdjęcia</a><a class="b gr" href="/admin/?p=settings">☎ Zmień telefon / dane</a><a class="b gr" href="/admin/?p=services">🛠 Usługi</a><a class="b gr" href="/admin/?p=messages">✉ Wiadomości</a></div></div>

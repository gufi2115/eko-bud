<?php defined('ADMIN') or exit;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = db()->prepare('SELECT password_hash FROM admins WHERE id=?');
    $st->execute([$_SESSION['admin_id']]);
    $hash = (string)$st->fetchColumn();
    $n1 = (string)($_POST['new'] ?? '');
    if (!password_verify((string)($_POST['current'] ?? ''), $hash)) back('account', 'Obecne hasło jest nieprawidłowe.', 'err');
    if (mb_strlen($n1) < 10) back('account', 'Nowe hasło musi mieć min. 10 znaków.', 'err');
    if ($n1 !== (string)($_POST['new2'] ?? '')) back('account', 'Nowe hasła nie są takie same.', 'err');
    db()->prepare('UPDATE admins SET password_hash=? WHERE id=?')->execute([password_hash($n1, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
    session_regenerate_id(true);
    back('account', 'Hasło zostało zmienione.');
}
?>
<form method="post" class="card"><?= csrf_field() ?><strong>Zmiana hasła</strong>
<label>Obecne hasło<input type="password" name="current" required autocomplete="current-password"></label>
<label>Nowe hasło (min. 10 znaków)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
<label>Powtórz nowe hasło<input type="password" name="new2" required minlength="10" autocomplete="new-password"></label>
<div class="row"><button class="b">Zmień hasło</button></div></form>
<form method="post" action="/admin/backup.php" class="card"><?= csrf_field() ?><strong>Kopia zapasowa</strong>
<p>Pobierze plik ZIP z bazą danych i wszystkimi zdjęciami.</p><div class="row"><button class="b">⬇ Pobierz kopię zapasową</button></div></form>

<?php defined('ADMIN') or exit;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['act'] ?? 'upload');
    $id = (int)($_POST['id'] ?? 0);
    if ($act === 'upload') {
        $ok = 0; $errs = [];
        $fl = $_FILES['photos'] ?? null;
        if ($fl && is_array($fl['name'])) {
            foreach ($fl['name'] as $i => $name) {
                if ($fl['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                $file = ['name' => $name, 'type' => $fl['type'][$i], 'tmp_name' => $fl['tmp_name'][$i], 'error' => $fl['error'][$i], 'size' => $fl['size'][$i]];
                try {
                    $r = image_save_upload($file);
                    $pos = (int)db()->query('SELECT COALESCE(MAX(position),0)+1 FROM gallery')->fetchColumn();
                    db()->prepare('INSERT INTO gallery(base,w,h,tw,th,position) VALUES(?,?,?,?,?,?)')
                        ->execute([$r['base'], $r['w'], $r['h'], $r['tw'], $r['th'], $pos]);
                    $ok++;
                } catch (RuntimeException $ex) { $errs[] = e($name) . ': ' . e($ex->getMessage()); }
            }
        }
        $m = "Dodano zdjęć: $ok." . ($errs ? '<br>Błędy:<br>' . implode('<br>', $errs) : '');
        back('gallery', $m, $errs ? 'err' : 'ok');
    }
    switch ($act) {
        case 'save': db()->prepare('UPDATE gallery SET alt=? WHERE id=?')->execute([mb_substr(trim((string)($_POST['alt'] ?? '')), 0, 200), $id]); back('gallery', 'Zapisano opis.');
        case 'up': case 'down': move_row('gallery', $id, $act); back('gallery', 'Zmieniono kolejność.');
        case 'hero':
            db()->exec('UPDATE gallery SET is_hero=0');
            db()->prepare('UPDATE gallery SET is_hero=1 WHERE id=?')->execute([$id]);
            back('gallery', 'Ustawiono zdjęcie główne.');
        case 'delete':
            $st = db()->prepare('SELECT base FROM gallery WHERE id=?'); $st->execute([$id]);
            if ($b = $st->fetchColumn()) { image_delete((string)$b); db()->prepare('DELETE FROM gallery WHERE id=?')->execute([$id]); }
            back('gallery', 'Usunięto zdjęcie.');
    }
}
$rows = db()->query('SELECT * FROM gallery ORDER BY position,id')->fetchAll();
?>
<form method="post" enctype="multipart/form-data" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="upload">
<label id="drop" for="photos">📷 Dotknij, aby wybrać zdjęcia z telefonu<br><small>lub przeciągnij je tutaj (JPG, PNG, WebP, do 20 MB każde)</small>
<input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple></label>
<noscript><div class="row"><button class="b">Wgraj</button></div></noscript></form>
<div class="tiles">
<?php foreach ($rows as $r): ?>
<form method="post" class="card tile"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<img src="/uploads/thumb/<?= e($r['base']) ?>.jpg" alt="" width="<?= (int)$r['tw'] ?>" height="<?= (int)$r['th'] ?>" loading="lazy">
<?php if ($r['is_hero']): ?><p><span class="hero-b">★ Zdjęcie główne</span></p><?php endif; ?>
<label>Opis zdjęcia (alt)<input name="alt" value="<?= e($r['alt']) ?>" maxlength="200"></label>
<div class="row"><button class="b sm" name="act" value="save">Zapisz</button><button class="b sm gr" name="act" value="up" aria-label="W górę">◀</button><button class="b sm gr" name="act" value="down" aria-label="W dół">▶</button></div>
<div class="row"><button class="b sm gr" name="act" value="hero">★ Główne</button><button class="b sm red" name="act" value="delete" data-confirm="Usunąć to zdjęcie?">Usuń</button></div></form>
<?php endforeach; if (!$rows): ?><p class="muted">Brak zdjęć. Dodaj pierwsze powyżej.</p><?php endif; ?></div>

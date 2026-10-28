<?php defined('ADMIN') or exit;
/** Oczekuje: $table ('services'|'advantages'), $p, $noun */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['act'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 200);
    $desc = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 1000);
    switch ($act) {
        case 'add':
            if ($title === '') back($p, 'Podaj tytuł.', 'err');
            $pos = (int)db()->query("SELECT COALESCE(MAX(position),0)+1 FROM $table")->fetchColumn();
            db()->prepare("INSERT INTO $table(title, description, position) VALUES(?,?,?)")->execute([$title, $desc, $pos]);
            back($p, 'Dodano.');
        case 'save':
            if ($title === '') back($p, 'Tytuł nie może być pusty.', 'err');
            db()->prepare("UPDATE $table SET title=?, description=?, visible=? WHERE id=?")
                ->execute([$title, $desc, isset($_POST['visible']) ? 1 : 0, $id]);
            back($p, 'Zapisano.');
        case 'up': case 'down': move_row($table, $id, $act); back($p, 'Zmieniono kolejność.');
        case 'delete': db()->prepare("DELETE FROM $table WHERE id=?")->execute([$id]); back($p, 'Usunięto.');
    }
    back($p, 'Nieznana akcja.', 'err');
}
$rows = db()->query("SELECT * FROM $table ORDER BY position,id")->fetchAll();
?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="add">
<strong>Dodaj: <?= e($noun) ?></strong>
<label>Tytuł<input name="title" required maxlength="200"></label>
<label>Opis<textarea name="description" rows="3" maxlength="1000"></textarea></label>
<div class="row"><button class="b">Dodaj</button></div></form>
<?php foreach ($rows as $r): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<label>Tytuł<input name="title" value="<?= e($r['title']) ?>" required maxlength="200"></label>
<label>Opis<textarea name="description" rows="3" maxlength="1000"><?= e($r['description']) ?></textarea></label>
<label><input type="checkbox" name="visible" <?= $r['visible'] ? 'checked' : '' ?>> Widoczne na stronie</label>
<div class="row"><button class="b" name="act" value="save">Zapisz</button>
<button class="b gr" name="act" value="up" aria-label="W górę">▲</button><button class="b gr" name="act" value="down" aria-label="W dół">▼</button>
<button class="b red" name="act" value="delete" data-confirm="Na pewno usunąć?">Usuń</button></div></form>
<?php endforeach; ?>

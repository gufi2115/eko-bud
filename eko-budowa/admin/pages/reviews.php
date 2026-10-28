<?php defined('ADMIN') or exit;
$hasKey = env('GOOGLE_PLACES_API_KEY') !== '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['act'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $author = mb_substr(trim((string)($_POST['author'] ?? '')), 0, 100);
    $text = mb_substr(trim((string)($_POST['text'] ?? '')), 0, 2000);
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $src = ($_POST['source'] ?? '') === 'google' ? 'google' : 'own';
    $date = mb_substr(trim((string)($_POST['review_date'] ?? '')), 0, 20);
    switch ($act) {
        case 'add':
            if ($author === '' || $text === '') back('reviews', 'Podaj autora i treść opinii.', 'err');
            $pos = (int)db()->query('SELECT COALESCE(MAX(position),0)+1 FROM reviews')->fetchColumn();
            db()->prepare('INSERT INTO reviews(author,rating,text,source,review_date,position) VALUES(?,?,?,?,?,?)')
                ->execute([$author, $rating, $text, $src, $date, $pos]);
            back('reviews', 'Dodano opinię.');
        case 'save':
            if ($author === '' || $text === '') back('reviews', 'Autor i treść są wymagane.', 'err');
            db()->prepare('UPDATE reviews SET author=?, rating=?, text=?, source=?, review_date=?, visible=? WHERE id=?')
                ->execute([$author, $rating, $text, $src, $date, isset($_POST['visible']) ? 1 : 0, $id]);
            back('reviews', 'Zapisano.');
        case 'up': case 'down': move_row('reviews', $id, $act); back('reviews', 'Zmieniono kolejność.');
        case 'delete': db()->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]); back('reviews', 'Usunięto.');
        case 'gsave':
            $pid = trim((string)($_POST['google_place_id'] ?? ''));
            $rate = str_replace(',', '.', trim((string)($_POST['google_rating'] ?? '')));
            $cnt = trim((string)($_POST['google_rating_count'] ?? ''));
            $u1 = trim((string)($_POST['google_reviews_url'] ?? ''));
            $u2 = trim((string)($_POST['google_write_url'] ?? ''));
            if ($pid !== '' && !preg_match('/^[A-Za-z0-9_-]{10,200}$/', $pid)) back('reviews', 'Nieprawidłowy Place ID.', 'err');
            if ($rate !== '' && (!is_numeric($rate) || $rate < 0 || $rate > 5)) back('reviews', 'Ocena musi być liczbą od 0 do 5.', 'err');
            if ($cnt !== '' && !ctype_digit($cnt)) back('reviews', 'Liczba opinii musi być liczbą całkowitą.', 'err');
            foreach ([$u1, $u2] as $u) if ($u !== '' && !preg_match('#^https://#i', $u)) back('reviews', 'Linki muszą zaczynać się od https://', 'err');
            foreach (['google_place_id' => $pid, 'google_rating' => $rate, 'google_rating_count' => $cnt,
                      'google_reviews_url' => $u1, 'google_write_url' => $u2] as $k => $v) setting_set($k, $v);
            back('reviews', 'Zapisano ustawienia Google.');
        case 'gsync':
            [$ok, $m] = google_sync();
            back('reviews', e($m), $ok ? 'ok' : 'err');
    }
    back('reviews', 'Nieznana akcja.', 'err');
}
$rows = db()->query('SELECT * FROM reviews ORDER BY position,id')->fetchAll();
function rev_fields(array $r = []): void { ?>
<label>Autor<input name="author" required maxlength="100" value="<?= e($r['author'] ?? '') ?>"></label>
<label>Ocena<select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"<?= (int)($r['rating'] ?? 5) === $i ? ' selected' : '' ?>><?= str_repeat('★', $i) ?> (<?= $i ?>)</option><?php endfor; ?></select></label>
<label>Treść opinii<textarea name="text" rows="4" required maxlength="2000"><?= e($r['text'] ?? '') ?></textarea></label>
<label>Data (opcjonalnie, np. 2026-09-14)<input name="review_date" maxlength="20" value="<?= e($r['review_date'] ?? '') ?>"></label>
<label>Źródło<select name="source"><option value="own">Własna / od klienta</option><option value="google"<?= ($r['source'] ?? '') === 'google' ? ' selected' : '' ?>>Google (przepisana ręcznie)</option></select></label>
<?php } ?>
<form method="post" class="card"><?= csrf_field() ?><strong>Połączenie z Google</strong>
<p class="muted">Podaj Place ID firmy (Google Maps → „Place ID Finder"). Wtedy na stronie pojawią się przyciski „Zobacz opinie w Google" i „Wystaw opinię". Ocenę i liczbę opinii możesz wpisać ręcznie albo pobrać automatycznie.</p>
<label>Place ID<input name="google_place_id" value="<?= e(setting('google_place_id') ?: env('GOOGLE_PLACE_ID')) ?>" maxlength="200"></label>
<label>Średnia ocena w Google (np. 4,9)<input name="google_rating" value="<?= e(setting('google_rating')) ?>"></label>
<label>Liczba opinii w Google<input name="google_rating_count" inputmode="numeric" value="<?= e(setting('google_rating_count')) ?>"></label>
<label>Własny link do opinii (opcjonalnie, nadpisuje Place ID)<input name="google_reviews_url" type="url" value="<?= e(setting('google_reviews_url')) ?>"></label>
<label>Własny link „Wystaw opinię" (opcjonalnie)<input name="google_write_url" type="url" value="<?= e(setting('google_write_url')) ?>"></label>
<div class="row"><button class="b" name="act" value="gsave">Zapisz</button>
<?php if ($hasKey): ?><button class="b gr" name="act" value="gsync">⟳ Pobierz opinie z Google</button><?php endif; ?></div>
<?php if (!$hasKey): ?><p class="muted">Automatyczne pobieranie jest wyłączone (brak GOOGLE_PLACES_API_KEY w .env).</p><?php endif; ?></form>

<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="add"><strong>Dodaj opinię</strong>
<?php rev_fields(); ?><div class="row"><button class="b">Dodaj</button></div></form>

<?php foreach ($rows as $r): ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<?php rev_fields($r); ?>
<label><input type="checkbox" name="visible" <?= $r['visible'] ? 'checked' : '' ?>> Widoczna na stronie</label>
<div class="row"><button class="b" name="act" value="save">Zapisz</button>
<button class="b gr" name="act" value="up" aria-label="W górę">▲</button><button class="b gr" name="act" value="down" aria-label="W dół">▼</button>
<button class="b red" name="act" value="delete" data-confirm="Usunąć opinię?">Usuń</button></div></form>
<?php endforeach; ?>

<?php defined('ADMIN') or exit;
$fields = [
 'company_name' => ['Nazwa firmy', 'text'], 'tagline' => ['Slogan', 'area'], 'phone' => ['Telefon', 'text'],
 'phone_hours' => ['Godziny kontaktu', 'text'], 'email' => ['E-mail', 'email'], 'address' => ['Adres', 'text'],
 'area' => ['Obszar działania', 'text'], 'facebook' => ['Link do Facebooka', 'url'], 'about' => ['Opis „O nas"', 'area'],
 'hero_title' => ['Nagłówek na górze strony', 'text'], 'hero_subtitle' => ['Hasła pod nagłówkiem', 'text'],
 'hero_button' => ['Tekst przycisku', 'text'], 'seo_title' => ['SEO: tytuł strony (title)', 'text'],
 'seo_description' => ['SEO: opis strony (description)', 'area'],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vals = [];
    foreach ($fields as $k => $_) $vals[$k] = mb_substr(trim((string)($_POST[$k] ?? '')), 0, 2000);
    if ($vals['email'] !== '' && !filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) back('settings', 'Nieprawidłowy adres e-mail.', 'err');
    if ($vals['facebook'] !== '' && !preg_match('#^https://#i', $vals['facebook'])) back('settings', 'Link do Facebooka musi zaczynać się od https://', 'err');
    if (trim($vals['phone']) === '' || trim($vals['company_name']) === '') back('settings', 'Nazwa i telefon są wymagane.', 'err');
    foreach ($vals as $k => $v) setting_set($k, $v);
    back('settings', 'Zapisano zmiany.');
}
?>
<form method="post" class="card"><?= csrf_field() ?>
<?php foreach ($fields as $k => [$label, $type]): ?>
<label><?= e($label) ?>
<?php if ($type === 'area'): ?><textarea name="<?= $k ?>" rows="4" maxlength="2000"><?= e(setting($k)) ?></textarea>
<?php else: ?><input name="<?= $k ?>" type="<?= $type ?>" value="<?= e(setting($k)) ?>" maxlength="2000"><?php endif; ?></label>
<?php endforeach; ?>
<div class="row"><button class="b">Zapisz</button></div></form>

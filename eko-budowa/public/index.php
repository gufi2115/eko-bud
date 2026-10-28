<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$site = rtrim(env('SITE_URL', 'http://localhost:8080'), '/');
$s = static fn(string $k): string => setting($k);
$services = db()->query('SELECT * FROM services WHERE visible=1 ORDER BY position,id')->fetchAll();
$advs = db()->query('SELECT * FROM advantages WHERE visible=1 ORDER BY position,id')->fetchAll();
$photos = db()->query('SELECT * FROM gallery ORDER BY position,id')->fetchAll();
$hero = db()->query('SELECT * FROM gallery WHERE is_hero=1 LIMIT 1')->fetch() ?: null;
$reviews = db()->query('SELECT * FROM reviews WHERE visible=1 ORDER BY position,id')->fetchAll();
$gid = preg_match('/^[A-Za-z0-9_-]{10,200}$/', setting('google_place_id')) ? setting('google_place_id') : '';
$httpsOnly = static fn(string $u): string => preg_match('#^https://#i', $u) ? $u : '';
$gSee = $httpsOnly(setting('google_reviews_url')) ?: ($gid ? 'https://search.google.com/local/reviews?placeid=' . $gid : '');
$gWrite = $httpsOnly(setting('google_write_url')) ?: ($gid ? 'https://search.google.com/local/writereview?placeid=' . $gid : '');
$gRating = (float)str_replace(',', '.', setting('google_rating'));
$gCount = (int)setting('google_rating_count');
$showReviews = $reviews || $gSee || $gWrite;
function stars(int $n): string { $n = max(0, min(5, $n)); return str_repeat('★', $n) . str_repeat('☆', 5 - $n); }
$tel = '+48' . preg_replace('/\D/', '', $s('phone'));
$ogImg = $hero ? "$site/uploads/full/{$hero['base']}.jpg" : '';
$mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($s('address')) . '&output=embed';

$ld = [
    '@context' => 'https://schema.org', '@type' => 'HomeAndConstructionBusiness',
    'name' => $s('company_name'), 'description' => $s('seo_description'), 'url' => $site . '/',
    'telephone' => $tel, 'email' => $s('email'), 'areaServed' => ['Gdańsk', 'Sopot', 'Gdynia'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'ul. Józefa Wąsika', 'postalCode' => '80-180',
                  'addressLocality' => 'Gdańsk', 'addressCountry' => 'PL'],
    'sameAs' => array_values(array_filter([$s('facebook')])),
    'priceRange' => 'Darmowa wycena',
];
if ($ogImg) $ld['image'] = $ogImg;

function pic(array $p, string $dir, string $alt, string $cls = '', bool $lazy = true): string
{
    $w = $dir === 'thumb' ? $p['tw'] : $p['w'];
    $h = $dir === 'thumb' ? $p['th'] : $p['h'];
    $u = "/uploads/$dir/" . $p['base'];
    return '<picture><source type="image/webp" srcset="' . e($u) . '.webp"><img src="' . e($u) . '.jpg" alt="' . e($alt)
        . '" width="' . $w . '" height="' . $h . '"' . ($lazy ? ' loading="lazy" decoding="async"' : '')
        . ($cls ? ' class="' . $cls . '"' : '') . '></picture>';
}
?><!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($s('seo_title')) ?></title>
<meta name="description" content="<?= e($s('seo_description')) ?>">
<link rel="canonical" href="<?= e($site) ?>/">
<meta name="theme-color" content="#064e3b">
<meta property="og:type" content="website">
<meta property="og:locale" content="pl_PL">
<meta property="og:title" content="<?= e($s('seo_title')) ?>">
<meta property="og:description" content="<?= e($s('seo_description')) ?>">
<meta property="og:url" content="<?= e($site) ?>/">
<?php if ($ogImg): ?><meta property="og:image" content="<?= e($ogImg) ?>"><?php endif; ?>
<link rel="stylesheet" href="/assets/css/style.css">
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script src="/assets/js/app.js" defer></script>
</head>
<body>
<a class="skip" href="#main">Przejdź do treści</a>
<header class="top">
  <div class="wrap bar">
    <a class="brand" href="#top"><?= e($s('company_name')) ?></a>
    <button id="burger" class="burger" aria-label="Menu" aria-expanded="false" aria-controls="nav"><span></span><span></span><span></span></button>
    <nav id="nav" aria-label="Główne">
      <a href="#o-nas">O nas</a><a href="#oferta">Oferta</a><a href="#galeria">Galeria</a>
      <a href="#dlaczego-my">Dlaczego my</a><?php if ($showReviews): ?><a href="#opinie">Opinie</a><?php endif; ?><a href="#kontakt">Kontakt</a>
      <a class="btn sm" href="tel:<?= e($tel) ?>">Zadzwoń</a>
    </nav>
  </div>
</header>

<main id="main">
<section class="hero" id="top">
  <?php if ($hero) echo pic($hero, 'full', $hero['alt'] ?: $s('hero_title'), 'hero-img', false); ?>
  <div class="wrap hero-in">
    <h1><?= e($s('hero_title')) ?></h1>
    <p class="lead"><?= e($s('tagline')) ?></p>
    <p class="tags"><?= e($s('hero_subtitle')) ?></p>
    <a class="btn" href="#kontakt"><?= e($s('hero_button')) ?></a>
    <a class="btn ghost" href="tel:<?= e($tel) ?>"><?= e($s('phone')) ?></a>
  </div>
</section>

<section class="wrap sec" id="o-nas">
  <h2>O nas</h2>
  <p class="about"><?= nl2br(e($s('about'))) ?></p>
  <p class="muted">Obszar działania: <?= e($s('area')) ?></p>
</section>

<section class="alt" id="oferta"><div class="wrap sec">
  <h2>Oferta</h2>
  <div class="grid">
  <?php foreach ($services as $i => $sv): ?>
    <article class="card"><span class="num"><?= $i + 1 ?></span><h3><?= e($sv['title']) ?></h3><p><?= e($sv['description']) ?></p></article>
  <?php endforeach; ?>
  </div>
</div></section>

<?php if ($photos): ?>
<section class="wrap sec" id="galeria">
  <h2>Galeria realizacji</h2>
  <div class="gal">
  <?php foreach ($photos as $p): $alt = $p['alt'] ?: 'Realizacja – ' . $s('company_name'); ?>
    <a href="/uploads/full/<?= e($p['base']) ?>.jpg" data-lb data-base="<?= e($p['base']) ?>" data-alt="<?= e($alt) ?>"><?= pic($p, 'thumb', $alt) ?></a>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="alt" id="dlaczego-my"><div class="wrap sec">
  <h2>Dlaczego my</h2>
  <div class="grid">
  <?php foreach ($advs as $a): ?>
    <article class="card"><h3><?= e($a['title']) ?></h3><p><?= e($a['description']) ?></p></article>
  <?php endforeach; ?>
  </div>
</div></section>

<?php if ($showReviews): ?>
<section class="wrap sec" id="opinie">
  <h2>Opinie klientów</h2>
  <?php if ($gRating > 0): ?>
  <p class="grate"><span class="stars" role="img" aria-label="Ocena <?= e(number_format($gRating, 1, ',', '')) ?> na 5"><?= stars((int)round($gRating)) ?></span>
    <strong><?= e(number_format($gRating, 1, ',', '')) ?></strong> / 5 w Google<?= $gCount > 0 ? ' (' . $gCount . ' opinii)' : '' ?></p>
  <?php endif; ?>
  <?php if ($reviews): ?><div class="grid">
  <?php foreach ($reviews as $r): ?>
    <figure class="card rev">
      <div class="stars" role="img" aria-label="Ocena <?= (int)$r['rating'] ?> na 5"><?= stars((int)$r['rating']) ?></div>
      <blockquote><p><?= nl2br(e($r['text'])) ?></p></blockquote>
      <figcaption><strong><?= e($r['author']) ?></strong><?= $r['review_date'] ? ' • ' . e($r['review_date']) : '' ?>
        <?php if ($r['source'] === 'google'): ?><span class="badge">Opinia z Google</span><?php endif; ?></figcaption>
    </figure>
  <?php endforeach; ?></div><?php endif; ?>
  <p class="revbtn">
    <?php if ($gSee): ?><a class="btn ghost2" href="<?= e($gSee) ?>" target="_blank" rel="noopener">Zobacz opinie w Google</a><?php endif; ?>
    <?php if ($gWrite): ?><a class="btn" href="<?= e($gWrite) ?>" target="_blank" rel="noopener">Wystaw opinię</a><?php endif; ?>
  </p>
</section>
<?php endif; ?>

<section class="wrap sec" id="kontakt">
  <h2>Kontakt</h2>
  <div class="contact">
    <div>
      <p><strong>Telefon:</strong> <a href="tel:<?= e($tel) ?>"><?= e($s('phone')) ?></a><br><span class="muted">Godziny kontaktu: <?= e($s('phone_hours')) ?></span></p>
      <p><strong>E-mail:</strong> <a href="mailto:<?= e($s('email')) ?>"><?= e($s('email')) ?></a></p>
      <p><strong>Adres:</strong> <?= e($s('address')) ?></p>
      <?php if ($s('facebook')): ?><p><a href="<?= e($s('facebook')) ?>" target="_blank" rel="noopener">Znajdź nas na Facebooku</a></p><?php endif; ?>
      <form id="cform" method="post" action="/contact.php" novalidate>
        <?= csrf_field() ?>
        <div class="hp" aria-hidden="true"><label>Strona WWW<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <label>Imię<input name="name" required autocomplete="name"></label>
        <label>Telefon<input name="phone" type="tel" autocomplete="tel"></label>
        <label>E-mail<input name="email" type="email" autocomplete="email"></label>
        <label>Rodzaj remontu
          <select name="type"><?php foreach ($services as $sv): ?><option><?= e($sv['title']) ?></option><?php endforeach; ?><option>Inne</option></select>
        </label>
        <label>Wiadomość<textarea name="message" rows="5" required></textarea></label>
        <button class="btn" type="submit">Wyślij zapytanie</button>
        <p id="fmsg" role="status" aria-live="polite"></p>
      </form>
    </div>
    <iframe title="Mapa – <?= e($s('address')) ?>" src="<?= e($mapSrc) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" width="600" height="450"></iframe>
  </div>
</section>
</main>

<footer><div class="wrap">© <?= date('Y') ?> <?= e($s('company_name')) ?> • <?= e($s('area')) ?></div></footer>
<a class="callfab" href="tel:<?= e($tel) ?>" aria-label="Zadzwoń: <?= e($s('phone')) ?>">📞 Zadzwoń</a>
</body>
</html>

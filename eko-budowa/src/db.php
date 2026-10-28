<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DATA_DIR . '/eko.sqlite', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys=ON; PRAGMA journal_mode=WAL; PRAGMA busy_timeout=5000;');
    }
    return $pdo;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $cache[$key] ?? $default;
}

function setting_set(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings(key, value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value')
        ->execute([$key, $value]);
}

function run_migrations(): void
{
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '');
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT, login TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT NOT NULL DEFAULT '',
            position INTEGER NOT NULL DEFAULT 0, visible INTEGER NOT NULL DEFAULT 1);
        CREATE TABLE IF NOT EXISTS advantages (
            id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT NOT NULL DEFAULT '',
            position INTEGER NOT NULL DEFAULT 0, visible INTEGER NOT NULL DEFAULT 1);
        CREATE TABLE IF NOT EXISTS gallery (
            id INTEGER PRIMARY KEY AUTOINCREMENT, base TEXT NOT NULL UNIQUE,
            w INTEGER NOT NULL, h INTEGER NOT NULL, tw INTEGER NOT NULL, th INTEGER NOT NULL,
            alt TEXT NOT NULL DEFAULT '', position INTEGER NOT NULL DEFAULT 0,
            is_hero INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, phone TEXT NOT NULL DEFAULT '',
            email TEXT NOT NULL DEFAULT '', type TEXT NOT NULL DEFAULT '', message TEXT NOT NULL,
            ip TEXT NOT NULL DEFAULT '', is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT, author TEXT NOT NULL, rating INTEGER NOT NULL DEFAULT 5,
            text TEXT NOT NULL DEFAULT '', source TEXT NOT NULL DEFAULT 'own', ext_id TEXT UNIQUE,
            review_date TEXT NOT NULL DEFAULT '', position INTEGER NOT NULL DEFAULT 0, visible INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT, bucket TEXT NOT NULL, ip TEXT NOT NULL, ts INTEGER NOT NULL);
        CREATE INDEX IF NOT EXISTS idx_rate ON rate_limits(bucket, ip, ts);
    ");

    if ((int)$pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
        seed_defaults($pdo);
    }

    if (!admin_exists() && env('ADMIN_EMAIL') !== '' && env('ADMIN_PASSWORD') !== '') {
        create_admin(env('ADMIN_EMAIL'), env('ADMIN_PASSWORD'));
        echo "[migrate] Utworzono konto admina z .env\n";
    }
}

function seed_defaults(PDO $pdo): void
{
    $pdo->beginTransaction();
    $settings = [
        'company_name'    => 'Eko-Budowa – Remonty i Wykończenia Trójmiasto',
        'tagline'         => 'Zespół doświadczonych specjalistów, którzy z pasją realizują kompleksowe projekty remontowo-budowlane, dbając o każdy detal i terminowość.',
        'phone'           => '882 342 665',
        'phone_hours'     => '07:00–19:00',
        'email'           => 'kudlaciakl@gmail.com',
        'address'         => 'ul. Józefa Wąsika, 80-180 Gdańsk',
        'area'            => 'Gdańsk, Sopot, Gdynia i okolice',
        'facebook'        => 'https://www.facebook.com/profile.php?id=61566580022000',
        'about'           => 'Działamy na terenie całego Trójmiasta oraz okolic. Realizujemy remonty mieszkań ze stanu deweloperskiego, modernizacje z rynku wtórnego, łazienki, gładzie bezpyłowe oraz wykończenia pod klucz.',
        'hero_title'      => 'Remonty i wykończenia w Trójmieście',
        'hero_subtitle'   => 'Gdańsk, Sopot, Gdynia • Terminowość i jakość • Darmowa wycena',
        'hero_button'     => 'Poproś o darmową wycenę',
        'why_intro'       => 'Co nas wyróżnia',
        'seo_title'       => 'Eko-Budowa – remonty i wykończenia pod klucz | Gdańsk, Sopot, Gdynia',
        'seo_description' => 'Remonty mieszkań, łazienki, gładzie bezpyłowe i wykończenia pod klucz w Gdańsku, Sopocie i Gdyni. Terminowość, dbałość o detale, darmowa wycena.',
    ];
    $st = $pdo->prepare('INSERT INTO settings(key, value) VALUES(?,?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    $services = [
        ['Wykończenia pod klucz', 'Całościowa realizacja mieszkań deweloperskich oraz generalne remonty lokali z rynku wtórnego.'],
        ['Łazienki i glazurnictwo', 'Gres, płytki wielkoformatowe, szlifowanie narożników 45°, odpływy liniowe, hydroizolacje.'],
        ['Gładzie i malowanie', 'Gładzie bezpyłowe szlifowane mechanicznie z odkurzaczem, malowanie wałkiem i natryskowe.'],
        ['Sufity i zabudowy G-K', 'Ścianki działowe, sufity podwieszane, wnęki LED, zabudowy rur i pionów.'],
        ['Podłogi i stolarka', 'Panele winylowe, deska warstwowa, jodełka, listwy przypodłogowe, drzwi wewnętrzne.'],
        ['Hydraulika i elektryka', 'Przeróbki punktów wodno-kanalizacyjnych i elektrycznych, biały montaż armatury.'],
    ];
    $st = $pdo->prepare('INSERT INTO services(title, description, position) VALUES(?,?,?)');
    foreach ($services as $i => $s) {
        $st->execute([$s[0], $s[1], $i + 1]);
    }

    $adv = [
        ['Terminowość', 'Ustalamy realny harmonogram prac i dotrzymujemy go.'],
        ['Dbałość o detale', 'Precyzyjne wykończenia, równe linie i czysty plac budowy.'],
        ['Przejrzyste zasady', 'Kosztorys bez ukrytych opłat – wiesz, za co płacisz.'],
        ['Lokalna ekipa z Gdańska', 'Mieszkamy i pracujemy w Trójmieście. Oględziny są bezpłatne.'],
    ];
    $st = $pdo->prepare('INSERT INTO advantages(title, description, position) VALUES(?,?,?)');
    foreach ($adv as $i => $a) {
        $st->execute([$a[0], $a[1], $i + 1]);
    }
    $pdo->commit();
}

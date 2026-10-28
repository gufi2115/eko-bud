<?php
declare(strict_types=1);

/**
 * Opcjonalny import opinii z Google (Places API New). Wymaga GOOGLE_PLACES_API_KEY w .env
 * oraz Place ID (GOOGLE_PLACE_ID w .env lub pole w panelu). Google zwraca maks. 5 opinii.
 * @return array{0:bool,1:string} [sukces, komunikat]
 */
function google_sync(): array
{
    $key = env('GOOGLE_PLACES_API_KEY');
    $pid = setting('google_place_id') ?: env('GOOGLE_PLACE_ID');
    if ($key === '') return [false, 'Brak klucza GOOGLE_PLACES_API_KEY w pliku .env.'];
    if (!preg_match('/^[A-Za-z0-9_-]{10,200}$/', $pid)) return [false, 'Brak lub nieprawidłowy Place ID.'];

    $ctx = stream_context_create(['http' => [
        'method' => 'GET', 'timeout' => 15, 'ignore_errors' => true,
        'header' => "X-Goog-Api-Key: $key\r\nX-Goog-FieldMask: rating,userRatingCount,reviews\r\n",
    ]]);
    $raw = @file_get_contents("https://places.googleapis.com/v1/places/$pid?languageCode=pl", false, $ctx);
    if ($raw === false) return [false, 'Nie udało się połączyć z Google.'];
    $d = json_decode($raw, true);
    if (!is_array($d)) return [false, 'Nieprawidłowa odpowiedź Google.'];
    if (isset($d['error'])) return [false, 'Google: ' . ($d['error']['message'] ?? 'błąd')];

    if (isset($d['rating'])) setting_set('google_rating', (string)$d['rating']);
    if (isset($d['userRatingCount'])) setting_set('google_rating_count', (string)(int)$d['userRatingCount']);
    if ($pid !== setting('google_place_id')) setting_set('google_place_id', $pid);

    $n = 0;
    $st = db()->prepare("INSERT INTO reviews(author, rating, text, source, ext_id, review_date, position)
        VALUES(?,?,?,'google',?,?,?)
        ON CONFLICT(ext_id) DO UPDATE SET author=excluded.author, rating=excluded.rating, text=excluded.text, review_date=excluded.review_date");
    foreach ($d['reviews'] ?? [] as $r) {
        $ext = (string)($r['name'] ?? '');
        if ($ext === '') continue;
        $text = (string)($r['text']['text'] ?? $r['originalText']['text'] ?? '');
        $pos = (int)db()->query('SELECT COALESCE(MAX(position),0)+1 FROM reviews')->fetchColumn();
        $st->execute([
            mb_substr((string)($r['authorAttribution']['displayName'] ?? 'Klient Google'), 0, 100),
            max(1, min(5, (int)($r['rating'] ?? 5))), mb_substr($text, 0, 2000), $ext,
            substr((string)($r['publishTime'] ?? ''), 0, 10), $pos,
        ]);
        $n++;
    }
    return [true, "Zsynchronizowano opinie z Google ($n). Ocena: " . ($d['rating'] ?? '—') . ', liczba opinii: ' . ($d['userRatingCount'] ?? '—') . '.'];
}

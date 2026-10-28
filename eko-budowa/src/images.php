<?php
declare(strict_types=1);

const IMG_MAX_BYTES = 20 * 1024 * 1024;
const IMG_SIZES = ['full' => 1920, 'thumb' => 480];

/**
 * Przetwarza jeden plik z $_FILES: walidacja, obrót wg EXIF, zmiana rozmiaru,
 * zapis WebP + JPEG w dwóch rozmiarach. Rzuca RuntimeException z komunikatem po polsku.
 * @return array{base:string,w:int,h:int,tw:int,th:int}
 */
function image_save_upload(array $f): array
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nie udało się wgrać pliku (kod błędu ' . (int)$f['error'] . '). Sprawdź rozmiar zdjęcia.');
    }
    if ($f['size'] > IMG_MAX_BYTES) {
        throw new RuntimeException('Zdjęcie jest za duże (maksymalnie 20 MB).');
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Nieprawidłowy plik.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$mime]) || !in_array($ext, $allowed[$mime], true)) {
        throw new RuntimeException('Dozwolone formaty: JPG, PNG, WebP.');
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || $info[0] * $info[1] > 50_000_000) {
        throw new RuntimeException('Nieprawidłowy lub zbyt duży obraz (maks. 50 megapikseli).');
    }

    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']),
        'image/png'  => @imagecreatefrompng($f['tmp_name']),
        'image/webp' => @imagecreatefromwebp($f['tmp_name']),
    };
    if (!$src) {
        throw new RuntimeException('Nie można odczytać obrazu.');
    }

    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($f['tmp_name']);
        $rot = match ((int)($ex['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($rot !== 0 && ($r = imagerotate($src, $rot, 0))) {
            $src = $r;
        }
    }

    $sw = imagesx($src);
    $sh = imagesy($src);
    $base = bin2hex(random_bytes(12));
    $dims = [];

    foreach (IMG_SIZES as $dir => $max) {
        $scale = min(1, $max / max($sw, $sh));
        $w = max(1, (int)round($sw * $scale));
        $h = max(1, (int)round($sh * $scale));
        $dst = imagecreatetruecolor($w, $h);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);
        imageinterlace($dst, true);
        $path = UPLOAD_DIR . "/$dir/$base";
        imagewebp($dst, "$path.webp", 82);
        imagejpeg($dst, "$path.jpg", 82);
        imagedestroy($dst);
        $dims[$dir] = [$w, $h];
    }
    imagedestroy($src);

    return ['base' => $base, 'w' => $dims['full'][0], 'h' => $dims['full'][1],
            'tw' => $dims['thumb'][0], 'th' => $dims['thumb'][1]];
}

function image_delete(string $base): void
{
    if (!preg_match('/^[a-f0-9]{24}$/', $base)) {
        return;
    }
    foreach (array_keys(IMG_SIZES) as $dir) {
        foreach (['webp', 'jpg'] as $ext) {
            @unlink(UPLOAD_DIR . "/$dir/$base.$ext");
        }
    }
}

# Eko-Budowa – strona + panel administracyjny

PHP 8.3 + SQLite + Apache w Dockerze. Bez Composera i npm.

## Założenia
- Apache startuje jako root (port 80, `chown` wolumenów); procesy obsługujące żądania działają jako `www-data`.
- Zdjęcia są zapisywane jako WebP + JPEG (pełny 1920 px, miniatura 480 px). Kolejność zmienia się strzałkami (bez drag & drop).
- Wgrywanie zdjęć: bez zmian po stronie przeglądarki; limit jednego żądania to 64 MB (przy wielu dużych zdjęciach wgrywaj partiami).
- Limity: 5 prób logowania / 15 min i 5 zapytań z formularza / godz. na IP.
- Wersja HTTPS wymaga Docker Compose ≥ 2.24.

## a) Wdrożenie lokalne
```bash
cp .env.example .env
docker compose up -d --build
```
Strona: http://localhost:8080 • Panel: http://localhost:8080/admin (kreator ustawi login i hasło; albo wpisz `ADMIN_EMAIL` i `ADMIN_PASSWORD` w `.env` przed startem).

## b) VPS z domeną i HTTPS
1. Zainstaluj Dockera: `curl -fsSL https://get.docker.com | sh`
2. W DNS ustaw rekordy **A** dla `twojadomena.pl` i `www.twojadomena.pl` na IP serwera. Otwórz porty 80 i 443.
3. `git clone <repo> && cd eko-budowa && cp .env.example .env`
4. W `.env` ustaw `DOMAIN=twojadomena.pl`, `SITE_URL=https://twojadomena.pl`, `ADMIN_EMAIL` (używany też przez Let's Encrypt) oraz SMTP.
5. Uruchom: `docker compose -f docker-compose.yml -f docker-compose.https.yml up -d --build`
6. Wejdź na `https://twojadomena.pl/admin`.

## c) Aktualizacja bez utraty danych
```bash
git pull
docker compose up -d --build            # lub z oboma plikami compose dla HTTPS
```
Dane leżą w wolumenach `eko_data` i `eko_uploads`, przebudowa obrazu ich nie rusza. Nie używaj `docker compose down -v`.

## d) Backup i przywracanie
- Z panelu: **Hasło i kopia zapasowa → Pobierz kopię** (ZIP z bazą i zdjęciami).
- Automatycznie: `./backup.sh`, a w cronie (`crontab -e`): `0 3 * * * /sciezka/do/eko-budowa/backup.sh`
- Nazwy wolumenów sprawdzisz: `docker volume ls`; jeśli folder projektu nazywa się inaczej, skrypt sam to uwzględni.
- Przywracanie:
```bash
docker compose down
docker run --rm -v eko-budowa_eko_data:/d -v "$PWD/backups":/b alpine sh -c "rm -rf /d/* && tar xzf /b/eko_data-DATA.tgz -C /d"
docker run --rm -v eko-budowa_eko_uploads:/d -v "$PWD/backups":/b alpine sh -c "rm -rf /d/* && tar xzf /b/eko_uploads-DATA.tgz -C /d"
docker compose up -d
```

## e) Problemy
- **Uprawnienia do wolumenów**: `docker compose exec web chown -R www-data:www-data data uploads` lub restart kontenera (entrypoint ustawia je sam).
- **Port zajęty**: zmień `8080:80` w `docker-compose.yml` na np. `8081:80`.
- **Brak maili**: sprawdź SMTP w `.env` (Gmail: port 587 + hasło aplikacji, nie zwykłe hasło) i `docker compose logs web | grep mail`. Wiadomości i tak zapisują się w panelu.
- **Kreator nie wyświetla się**: konto admina już istnieje, wejdź na `/admin/login.php`.
- **Zapomniane hasło**: `docker compose exec web php -r 'require "src/bootstrap.php"; db()->prepare("UPDATE admins SET password_hash=? WHERE id=1")->execute([password_hash("NoweHaslo12345", PASSWORD_DEFAULT)]);'`

## Pomysły na rozwój
Strony podusług pod SEO • opinie klientów • przed/po (slider) • kategorie w galerii • powiadomienia SMS/Telegram • statystyki bez cookies • wiele kont admina i dziennik zmian • reCAPTCHA/Turnstile • kalkulator orientacyjnej wyceny.

## Opinie i połączenie z Google
Panel → **Opinie**. Są trzy poziomy integracji:
1. **Ręczne opinie** – dodajesz autora, ocenę i treść (np. za zgodą klienta). Sekcja „Opinie" na stronie pojawia się automatycznie.
2. **Link do Google (bez kluczy API)** – wpisz Place ID firmy (Google Maps → Place ID Finder). Strona pokaże przyciski „Zobacz opinie w Google" i „Wystaw opinię"; średnią ocenę i liczbę opinii wpisujesz ręcznie.
3. **Import automatyczny** – w `.env` ustaw `GOOGLE_PLACES_API_KEY` (Google Cloud → Places API (New), wymaga włączonych płatności) i Place ID, potem w panelu kliknij „Pobierz opinie z Google". Google zwraca maks. 5 opinii. Po pobraniu możesz je ukrywać i zmieniać ich kolejność.

Uwaga: regulamin Google Maps Platform ogranicza przechowywanie treści z API. Przed użyciem importu automatycznego sprawdź aktualne warunki; opcje 1 i 2 są wolne od tego ryzyka. Opinie z Google są oznaczane na stronie jako „Opinia z Google". Pamiętaj, żeby nie publikować opinii nieprawdziwych ani wybierać tylko pozytywnych.

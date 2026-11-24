# Wohlfühlraum Sunny: Stand der Website

Zum Weitermachen in einem neuen Chat: diese Datei zuerst lesen.
Repo: `infomalutiwa/Sunny-Mails-Beauty`, Branch `ccr-2ec575da-frrsfm` (Stand 31.10.2026). Kein Pull Request angelegt.
Vorschau (Firebase Hosting, Projekt `sunny-mails-beauty`): https://sunny-mails-beauty.web.app/

## Das Studio (aus den Instagram-Screenshots der Inhaberin)
- Wohlfühlraum Sunny, "Dein Ort zum Aufblühen", Kosmetik · Nails · Fußpflege. Neueröffnung 01.11.2025.
- Müßmattstraße 22, 79618 Rheinfelden (Baden). Telefon/WhatsApp 0173 3098632. Instagram @wohlfuehlraum_sunny.
- Zeiten: Mo 8–13, Di und Do 8–13 und 15–20, Mi geschlossen, Fr 10–16, Sa nach Vereinbarung, So geschlossen.
- Preise (Preislisten vom Studio, Stand 24.11.2026): Kosmetik inkl. Säurepeelings und Microneedling 49–139 €, Head Spa 49–119 €, Maniküre/Gel 32–60 €, Pediküre 45–60 €, Wimpern & Brauen 15–129 € (inkl. Wimpernverlängerung), Schulungen 199–649 € (nur auf der Preisseite, Anfrage per WhatsApp, nicht online buchbar).
  Head Spa steht auf der Kosmetik-Seite und der Preisseite (keine eigene Seite). Die Wohlfühlbehandlung (Fuß) ist nicht mehr in der Liste und wurde entfernt.
- Design (31.10.): Palette von Sunny: Elfenbein #FDFBF7, Sand #F3EFEA, Champagner-Gold #C8A97E–#B89355 (Buttons als Verlauf, dunkle Schrift), Text #2E2A27, Salbei #D8DFD5 als dezenter Akzent (Top-Leiste, Infoleiste, Preis-Bereich). Schriften wie im Logo: Raleway + Cormorant Garamond, Überschriften DM Serif. Vollbild-Hero-Video (Querformat) und Hochformat-Video fürs Handy, Marmor nur weich ausgeblendet. Name laut Logo: Wohlfühlraum Sunny.
- Marke: Braun, Gold, Creme, Serifenschrift. Logo in der Seite: Wortmarke als Text plus "W"-Monogramm (Platzhalter). Echte Logo-Datei (SVG/PNG) einsetzen, sobald vorhanden.

## Aufbau
- `index.html` (Root) leitet auf `business/` weiter, das ist die Website. Paketvergleich und Starter-Seite wurden entfernt.
- `business/`: 8 Seiten (Start, Kosmetik, Nägel, Fußpflege, Brauen & Wimpern, Preise, Galerie, Termin & Kontakt) plus Impressum, Datenschutz. Animationen, Vorher-Nachher, Terminbuchung, JSON-LD (BeautySalon, FAQ, Breadcrumb), Open Graph, `sitemap.xml`/`robots.txt` im Root.
- Business-Seiten werden mit einem Skript erzeugt (liegt nicht im Repo). Änderungen daher direkt in den HTML-Dateien machen oder das Skript neu schreiben.
- `assets/`: `style.css`, `business.css`, `business.js`, `booking.js`, `booking-config.js` (Leistungen, Preise, Zeiten; Dauer sind Schätzwerte). `img/`: Stimmungsbilder von Unsplash (kostenlos, auch gewerblich, ohne Namensnennung; Unsplash-Lizenz): spa-kerzen, spa-handtuecher, empfang, kosmetik, naegel, naegel-2, fussmassage, wimpern, creme, marmor. Vorher-Nachher (`haut-1..3`) sind echte Studiofotos. Die alten Studiofotos wurden auf Wunsch der Inhaberin entfernt. Später gern durch eigene, professionelle Fotos ersetzen.
- Domain: `wohlfuehlraum-sunny.de` (Hosting ALL-INKL, Account w022499d, Ordner `/wohlfuehlraum-sunny.de/`) steht in canonical, og:url, sitemap, robots. Upload per FTP: Inhalt von Repo-Root ohne .git, *.md, firebase-Dateien; `.htaccess` leitet / auf /business/ und regelt Caching. Firebase bleibt für die Buchung (Firestore) zuständig.

## E-Mail (seit 24.11.2026)
- Postfach `info@wohlfuehlraum-sunny.de` bei ALL-INKL. Versand über `api/kontakt.php` (PHP `mail()`, kein Passwort im Code).
- Kontaktformular auf `termin.html#kontakt` (Thema per `?thema=Schulung`), Mail ans Studio mit Reply-To der Kundin.
- Nach jeder Online-Buchung: Info-Mail ans Studio, Bestätigung mit .ics an die Kundin (nur wenn E-Mail angegeben). Der Server prüft per Firestore, dass der Termin wirklich belegt ist, und schickt pro Buchung nur einmal.
- Schutz: nur eigene Domain, Honeypot, Mindestzeit 3 s, max. 8 Anfragen pro IP und Stunde (`api/.store/`, gesperrt).
- Deploy: GitHub Actions `deploy-allinkl.yml` bei jedem Push auf den Branch, Secrets `FTP_USERNAME`/`FTP_PASSWORD`.
- `/mail` (Studio-Postfach mit Firebase-Login) wurde am 24.11.2026 auf Wunsch entfernt. Die Inhaberin liest alles im Mail-Konto info@ (iPhone-Mail per IMAP). Stornieren und Zeiten blockieren geht damit online nicht mehr, siehe Offen.

## Terminbuchung (Direktbuchung)
- `termin.html` + `assets/booking.js`: mehrere Leistungen wählbar, nur freie Zeiten in den Öffnungszeiten (`booking-config.js`), sofort gebucht, Kalenderdatei für die Kundin.
- Firestore: `termine/` (Kundendaten, nur Studio lesbar), `slots/{datum}_{minute}` (15-Min.-Blöcke, öffentlich lesbar, ohne Namen). Ein Block kann nur einmal angelegt werden, Doppelbuchungen sind per Regel ausgeschlossen. 12 Regeltests im Emulator bestanden.
- Push bei geschlossener Seite: kostenlose App ntfy, Adresse in `booking-config.js` bei `notify` eintragen. Echte Push/E-Mail-Bestätigung bräuchte Cloud Functions (Blaze-Tarif).
- Serverseitig in den Regeln erzwungen (nicht nur im Browser): Öffnungszeiten je Wochentag (müssen zu `hours` in `booking-config.js` passen, sonst beides ändern), mindestens ca. 2,5 Std. Vorlauf, höchstens 60 Tage voraus, keine Überlappung. 20 Regeltests im Emulator plus Browser-Durchlauf (parallele Doppelbuchung, Mehrfachleistung, Vorlaufzeit) bestanden.
- **Firestore-Regeln müssen deployt werden** (`firebase deploy --only firestore`), sonst schlägt die Buchung fehl.

## Offen
0. Ohne `/mail`: stornierte Termine bleiben online belegt, Urlaub/Pausen lassen sich nicht blockieren. Lösung bei Bedarf: Storno-Link in den Buchungs-Mails (Firestore-Regel mit Schlüssel) oder Firebase-Konsole.
1. Fotorechte klären: Gesichtsfotos (Vorher-Nachher) brauchen Einwilligung der Kundinnen. Nägel- und Fußfotos wirken wie Fremdbilder, eigene einsetzen.
2. Eigenes Foto für Augenbrauen & Wimpern und Head Spa (aktuell Platzhalter). Schulungspreise waren im PDF als Vorschlag markiert: vor Veröffentlichung bestätigen.
3. Dauer laut Preislisten in `booking-config.js` (bei Spannen die längere Zeit), Leistungen in der Buchung nach Bereich gruppiert. Anzahlung/Storno-Regeln gibt es für dieses Studio nicht, `deposit:null`.
4. Bei ALL-INKL: SSL-Zertifikat (Let's Encrypt) einrichten und HTTPS erzwingen, AV-Vertrag abschließen. In Firebase prüfen, dass Buchung und /mail-Login unter der neuen Domain funktionieren.
5. Rosa Stellen in Impressum und Datenschutz ausfüllen (Name Inhaberin, E-Mail, Rechtsform, Steuer-ID usw.), rechtlich prüfen.
6. Rheinfelden-Postleitzahl 79618 gegenprüfen.

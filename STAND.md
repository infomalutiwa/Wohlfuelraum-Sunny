# Wohlfühlraum Sunny: Stand der Website

Zum Weitermachen in einem neuen Chat: diese Datei zuerst lesen.
Repo: `infomalutiwa/Wohlfuelraum-Sunny` (früher Sunny-Mails-Beauty), Branch `claude/new-session-xv9mjl`.
Live: https://wohlfuehlraum-sunny.de/ (ALL-INKL). Branch `claude/new-session-xv9mjl`, Deploy automatisch per GitHub Actions.

## Das Studio (aus den Instagram-Screenshots der Inhaberin)
- Wohlfühlraum Sunny, "Dein Ort zum Aufblühen", Kosmetik · Nails · Fußpflege. Neueröffnung 01.11.2025.
- Müßmattstraße 22, 79618 Rheinfelden (Baden). Telefon/WhatsApp 0173 3098632. Instagram @wohlfuehlraum_sunny.
- Zeiten: Mo 8–13, Di und Do 8–13 und 15–20, Mi geschlossen, Fr 10–16, Sa nach Vereinbarung, So geschlossen.
- Preise (Preislisten vom Studio, Stand 24.11.2026): Kosmetik inkl. Säurepeelings und Microneedling 49–139 €, Head Spa 49–119 €, Maniküre/Gel 32–60 €, Pediküre 45–60 €, Wimpern & Brauen 15–129 € (inkl. Wimpernverlängerung), Schulungen 199–649 € (nur auf der Preisseite, Anfrage per WhatsApp, nicht online buchbar).
  Head Spa steht auf der Kosmetik-Seite und der Preisseite (keine eigene Seite). Die Wohlfühlbehandlung (Fuß) ist nicht mehr in der Liste und wurde entfernt.
- Design (31.10.): Palette von Sunny: Elfenbein #FDFBF7, Sand #F3EFEA, Champagner-Gold #C8A97E–#B89355 (Buttons als Verlauf, dunkle Schrift), Text #2E2A27, Salbei #D8DFD5 als dezenter Akzent (Top-Leiste, Infoleiste, Preis-Bereich). Schriften wie im Logo: Raleway + Cormorant Garamond, Überschriften DM Serif. Startbild seit 26.11.: Cremetiegel (Unsplash, `img/hero-creme.jpg` quer, `hero-creme-m.jpg` Handy) mit langsamem Zoom statt des Honig-Videos (alte Videos `hero*.mp4/webm` liegen noch in img/), Marmor nur weich ausgeblendet. Name laut Logo: Wohlfühlraum Sunny.
- Marke: Braun, Gold, Creme, Serifenschrift. Logo in der Seite: Wortmarke als Text plus "W"-Monogramm (Platzhalter). Echte Logo-Datei (SVG/PNG) einsetzen, sobald vorhanden.

## Aufbau
- `index.html` (Root) leitet auf `business/` weiter, das ist die Website. Paketvergleich und Starter-Seite wurden entfernt.
- `business/`: 8 Seiten (Start, Kosmetik, Nägel, Fußpflege, Brauen & Wimpern, Preise, Galerie, Termin & Kontakt) plus Impressum, Datenschutz. Animationen, Vorher-Nachher, Terminbuchung, JSON-LD (BeautySalon, FAQ, Breadcrumb), Open Graph, `sitemap.xml`/`robots.txt` im Root.
- Business-Seiten werden mit einem Skript erzeugt (liegt nicht im Repo). Änderungen daher direkt in den HTML-Dateien machen oder das Skript neu schreiben.
- `assets/`: `style.css`, `business.css`, `business.js`, `booking.js`, `booking-config.js` (Leistungen, Preise, Zeiten; Dauer sind Schätzwerte). `img/`: Stimmungsbilder von Unsplash (kostenlos, auch gewerblich, ohne Namensnennung; Unsplash-Lizenz): spa-kerzen, spa-handtuecher, empfang, creme-tiegel (ersetzt seit 26.11. das Honig-Bild in Einblicke/Galerie), kosmetik, naegel, naegel-2, fussmassage, wimpern, creme, marmor. Vorher-Nachher (`haut-1..3`) sind echte Studiofotos. Die alten Studiofotos wurden auf Wunsch der Inhaberin entfernt. Später gern durch eigene, professionelle Fotos ersetzen.
- Domain: `wohlfuehlraum-sunny.de` (Hosting ALL-INKL, Account w022499d, Ordner `/wohlfuehlraum-sunny.de/`) steht in canonical, og:url, sitemap, robots. Upload per FTP: Inhalt von Repo-Root ohne .git, *.md, firebase-Dateien; `.htaccess` leitet / auf /business/ und regelt Caching. Firebase bleibt für die Buchung (Firestore) zuständig.

## KLAPP und SEO (25.11.2026)
- Das Studio arbeitet mit KLAPP Skin Care Science. Material der Kundin (Bilder, Videos, Logo-PDF) liegt optimiert in `img/klapp/` (Logo als SVG, 4 Videos stumm als mp4+webm, je mit Vorschaubild).
- Kosmetik-Seite: Abschnitt `#klapp` mit 3 Produktkarten (Purify, PHA Toner, ExoGen) und 4 Videos (spielen nur, wenn sichtbar; Code in `business.js`). Startseite: KLAPP-Teaser und Ortstext für die lokale Suche. Galerie: 3 KLAPP-Bilder.
- Bewusst nicht übernommen: Prozentangaben und „8 Jahre jünger“ aus der ExoGen-Werbung (Werberecht), englische Erklär-Videos.
- SEO: Titel/Beschreibungen/Keywords pro Seite, erweitertes BeautySalon-JSON-LD, Sitemap mit Bildern, 301 auf /business/. Stichworte und To-dos für die Inhaberin: `SEO-STICHWORTE.md`.

## Buchung und E-Mail (seit 24.11.2026 alles bei ALL-INKL, Firebase entfernt)
- Server-Skripte in `api/`: `_lib.php` (Öffnungszeiten, Leistungen, Mail-Vorlagen, Datenbank), `termine.php` (Buchung, Storno, Studio-Kalender), `kontakt.php` (Kontaktformular), `booking-config.php` (liefert Zeiten und Leistungen an die Buchungsseite).
- **Leistungen und Öffnungszeiten nur noch in `api/_lib.php` ändern** (einzige Quelle).
- Datenbank: SQLite `api/.store/termine.sqlite` auf dem Webspace (gesperrt per .htaccess, nicht im Repo). Dort auch `secret.key` für signierte Links. Termine werden 90 Tage nach dem Termin automatisch gelöscht.
- Doppelbuchungen ausgeschlossen (eindeutige 15-Min.-Blöcke, Transaktion). Server prüft Öffnungszeiten, Feiertage BW, Raster, Vorlauf (120 Min.), max. 42 Tage voraus, Leistungen und Preise.
- Mails über PHP `mail()` von info@wohlfuehlraum-sunny.de: Studio bekommt jede Buchung mit Buttons „Kalender öffnen“ und „Termin stornieren“; Kundin bekommt Bestätigung mit .ics und „Termin absagen“-Link. Absagen lösen Mails an beide Seiten aus.
- Studio-Kalender ohne Login: geheimer Link `api/termine.php?a=studio&k=…` (steht in jeder Buchungs-Mail). Zeigt kommende Termine, stornieren, Zeiten blockieren (Urlaub, Pausen, telefonische Termine). Neuer Link = `api/.store/secret.key` auf dem Server löschen (macht auch alte Storno-Links ungültig).
- Kontaktformular auf `termin.html#kontakt` → Mail ans Studio.
- Deploy: GitHub Actions `deploy-allinkl.yml` bei jedem Push, Secrets `FTP_USERNAME`/`FTP_PASSWORD`.
- Firebase wird nicht mehr genutzt. Das alte Firebase-Projekt bitte in der Firebase-Konsole löschen.

## Offen
1. Fotorechte klären: Gesichtsfotos (Vorher-Nachher) brauchen Einwilligung der Kundinnen. Nägel- und Fußfotos wirken wie Fremdbilder, eigene einsetzen.
2. Eigenes Foto für Augenbrauen & Wimpern und Head Spa (aktuell Platzhalter). Schulungspreise waren im PDF als Vorschlag markiert: vor Veröffentlichung bestätigen.
3. Dauer laut Preislisten in `booking-config.js` (bei Spannen die längere Zeit), Leistungen in der Buchung nach Bereich gruppiert. Anzahlung/Storno-Regeln gibt es für dieses Studio nicht, `deposit:null`.
4. AV-Vertrag mit ALL-INKL besteht seit 25.10.2026 (MembersArea → Stammdaten → Auftragsverarbeitung, PDF). HTTPS läuft.
5. Impressum/Datenschutz ausgefüllt (Inhaberin Stana Todorovic, Einzelunternehmen, Termindaten max. 90 Tage). Offen: USt-IdNr. nur eintragen, falls eine vergeben wird (Steuernummer gehört nicht ins Impressum). Einwilligung der Personen auf Vorher-Nachher-Fotos sicherstellen.
6. Rheinfelden-Postleitzahl 79618 gegenprüfen.

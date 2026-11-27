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
- Design (31.10.): Palette von Sunny: Elfenbein #FDFBF7, Sand #F3EFEA, Champagner-Gold #C8A97E–#B89355 (Buttons als Verlauf, dunkle Schrift), Text #2E2A27, Salbei #D8DFD5 als dezenter Akzent (Top-Leiste, Infoleiste, Preis-Bereich). Schriften wie im Logo: Raleway + Cormorant Garamond, Überschriften DM Serif. Startvideo: ursprüngliche Diashow (Nägel, Fuß, Auge, Gesicht, Empfang), seit 26.11. ohne das Honig-Bild am Anfang (`img/hero.*` quer, `hero-m.*` Handy, Cache-Version ?v=3), Marmor nur weich ausgeblendet. Name laut Logo: Wohlfühlraum Sunny.
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
- **Studio-Kalender (seit 27.11.2026): `/studio/`** (App im Apple-Stil, `studio/index.html`, `studio.css`, `studio.js`, Schnittstelle `api/studio.php`). **Anmeldung mit Passwort** (seit 27.11.2026). Erster Start: „Zugang einrichten“ schickt einen Link an info@wohlfuehlraum-sunny.de, darüber legt Stana das Passwort fest (mind. 10 Zeichen). „Passwort vergessen?“ funktioniert genauso. Links sind 1 Stunde gültig und nur einmal nutzbar. Angemeldet bleibt man 30 Tage pro Gerät, „Auf allen Geräten abmelden“ unter Sperrzeiten → Konto.
  - Sicherheit: Passwort nur als Hash (password_hash), Sitzung als zufälliges Token in einem HttpOnly/Secure/SameSite=Strict-Cookie (nur /api/), in der DB nur als SHA-256. Max. 8 Anmeldeversuche pro Stunde und IP. Alle Änderungen nur von der eigenen Domain und nur als JSON (CSRF-Schutz). `/studio/.htaccess` setzt eine strenge Content-Security-Policy. Die Mail-Links enthalten keinen Schlüssel mehr, Stornieren durch das Studio geht nur im Kalender.
  - Schutz der Online-Buchung: pro IP max. 5 Buchungen/Stunde und 8/Tag, insgesamt max. 40 neue Buchungen/Tag, Formular muss mind. 5 Sek. offen sein, Honeypot, max. 3 offene Online-Termine pro Telefonnummer/E-Mail.
  - Tests: Schnittstellen-Test (63 Prüfungen) und Browser-Durchklicktest (23 Prüfungen) liefen am 27.11.2026 fehlerfrei (Skripte lagen nur in der Sitzung, nicht im Repo).
  - Die App kann als App zum Home-Bildschirm hinzugefügt werden.
  - Ansichten Tag/Woche/Monat (Handy: Monat mit Punkten + Tagesliste, Tab-Bar). Offene Stunden weiß, geschlossene sandfarben. Farben: grün = online gebucht, gold = selbst eingetragen, schraffiert = blockiert, blau = Urlaub, rot = Feiertag/geschlossen.
  - Eigene Termine anlegen/bearbeiten/verschieben (Leistungen mit Dauer und Preis, auch außerhalb der Öffnungszeiten, z. B. samstags), optional Bestätigungs-Mail an die Kundin. Überschneidungen werden angezeigt, „Trotzdem sichern“ möglich. Stornieren mit optionaler Mail.
  - Zeit blockieren (Pause, Arzttermin). Sperrzeiten über ganze Tage: Urlaub, Geschlossen, Feiertag, Sonstiges (Tabelle `closures`). Gesetzliche Feiertage BW automatisch gesperrt, einzeln „geöffnet“ schaltbar (Eintrag `kind=offen`).
  - Eingang: neue Online-Buchungen, Absagen durch Kundinnen, Nachrichten aus dem Kontaktformular (Tabelle `messages`, 90 Tage). Aus einer Nachricht direkt „Als Termin eintragen“.
  - Kundinnen sehen auf der Buchungsseite pro Tag „frei“, „wenig frei“, „belegt“, „Urlaub“, „Feiertag“ und einen Hinweis auf Urlaub im Buchungszeitraum (`termine.php?a=busy&from&to`, `booking-config.php` liefert `closedInfo`).
  - `api/.store/secret.key` signiert die Absage-Links der Kundinnen und die Passwort-Links. Löschen macht alte Absage-Links ungültig.
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

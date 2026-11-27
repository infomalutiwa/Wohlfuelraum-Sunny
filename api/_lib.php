<?php
/*
 * Wohlfühlraum Sunny – gemeinsame Einstellungen und Hilfsfunktionen für die Server-Skripte.
 * Hier stehen Öffnungszeiten und Leistungen (einzige Quelle: die Website lädt sie über api/booking-config.php).
 * Die Termine liegen in einer SQLite-Datenbank in api/.store/ (von außen gesperrt).
 */
if (!defined('SUNNY')) { http_response_code(404); exit; }
date_default_timezone_set('Europe/Berlin');

const STUDIO_MAIL = 'info@wohlfuehlraum-sunny.de';
const STUDIO_NAME = 'Wohlfühlraum Sunny';
const SITE        = 'https://wohlfuehlraum-sunny.de';
const PHONE       = '0173 3098632';
const PHONE_INTL  = '+491733098632';
const ADDRESS     = 'Müßmattstraße 22, 79618 Rheinfelden (Baden)';
const ALLOWED_HOSTS = ['wohlfuehlraum-sunny.de', 'www.wohlfuehlraum-sunny.de', 'wohlfuehlraum-sunny.de.w022499d.kasserver.com'];
const KEEP_DAYS   = 90;   // Termindaten werden 90 Tage nach dem Termin gelöscht (siehe Datenschutzerklärung)
const BLOCK       = 15;   // Raster der Belegung in Minuten

/* Buchungsregeln */
const BOOKING = [
  'daysAhead' => 42,   // so viele Tage im Voraus buchbar
  'minLead'   => 120,  // frühestens so viele Minuten ab jetzt
  'slotStep'  => 30,   // Startzeiten im Abstand von 30 Minuten
  // Wochentag (0 = Sonntag) => [[von, bis], …] in Stunden. Fehlt ein Tag, ist er online nicht buchbar.
  'hours'     => [1 => [[8, 13]], 2 => [[8, 13], [15, 20]], 4 => [[8, 13], [15, 20]], 5 => [[10, 16]]],
];

/* Leistungen laut Preislisten (Stand November 2026). Bei Zeitspannen ist die längere Dauer eingetragen.
   price null = Preis nach Aufwand. */
const SERVICES = 
[
  ['id'=>'kos-kennen','cat'=>'Kosmetik','name'=>"Kennenlernbehandlung",'minutes'=>45,'price'=>60],
  ['id'=>'kos-klassisch','cat'=>'Kosmetik','name'=>"Klassische Gesichtsbehandlung",'minutes'=>60,'price'=>79],
  ['id'=>'kos-aqua','cat'=>'Kosmetik','name'=>"Aquafacial",'minutes'=>60,'price'=>99],
  ['id'=>'kos-antiaging','cat'=>'Kosmetik','name'=>"Anti-Aging Luxusbehandlung",'minutes'=>75,'price'=>109],
  ['id'=>'kos-sensitive','cat'=>'Kosmetik','name'=>"Sensitive Skin",'minutes'=>60,'price'=>79],
  ['id'=>'kos-clear','cat'=>'Kosmetik','name'=>"Clear Skin – unreine Haut",'minutes'=>60,'price'=>79],
  ['id'=>'kos-akne','cat'=>'Kosmetik','name'=>"Akne-Spezialbehandlung",'minutes'=>75,'price'=>89],
  ['id'=>'kos-hydrojelly','cat'=>'Kosmetik','name'=>"Hydrojelly Glow",'minutes'=>45,'price'=>59],
  ['id'=>'kos-luxury','cat'=>'Kosmetik','name'=>"Luxury Facial Ritual",'minutes'=>90,'price'=>139],
  ['id'=>'kos-teen','cat'=>'Kosmetik','name'=>"Teen Clean (bis 17 Jahre)",'minutes'=>45,'price'=>49],
  ['id'=>'kos-frucht','cat'=>'Kosmetik','name'=>"Fruchtsäurepeeling",'minutes'=>50,'price'=>89],
  ['id'=>'kos-milch','cat'=>'Kosmetik','name'=>"Milchsäurepeeling",'minutes'=>50,'price'=>85],
  ['id'=>'kos-needling','cat'=>'Kosmetik','name'=>"Microneedling",'minutes'=>60,'price'=>119],
  ['id'=>'hs-classic','cat'=>'Head Spa','name'=>"Head Spa Classic",'minutes'=>45,'price'=>69],
  ['id'=>'hs-deluxe','cat'=>'Head Spa','name'=>"Head Spa Deluxe",'minutes'=>60,'price'=>89],
  ['id'=>'hs-premium','cat'=>'Head Spa','name'=>"Head Spa Premium",'minutes'=>90,'price'=>119],
  ['id'=>'hs-kids','cat'=>'Head Spa','name'=>"Kids Head Spa (6–11 Jahre)",'minutes'=>30,'price'=>49],
  ['id'=>'hs-teen','cat'=>'Head Spa','name'=>"Teen Head Spa (12–17 Jahre)",'minutes'=>45,'price'=>59],
  ['id'=>'mani','cat'=>'Nägel','name'=>"Klassische Maniküre",'minutes'=>40,'price'=>32],
  ['id'=>'mani-lack','cat'=>'Nägel','name'=>"Maniküre mit Nagellack",'minutes'=>50,'price'=>37],
  ['id'=>'mani-shellac','cat'=>'Nägel','name'=>"Maniküre mit CND Shellac",'minutes'=>60,'price'=>45],
  ['id'=>'gel-neu','cat'=>'Nägel','name'=>"Gel-Neumodellage",'minutes'=>90,'price'=>60],
  ['id'=>'gel-verst','cat'=>'Nägel','name'=>"Gel-Naturnagelverstärkung",'minutes'=>90,'price'=>55],
  ['id'=>'gel-auf','cat'=>'Nägel','name'=>"Gel-Nägel auffüllen",'minutes'=>90,'price'=>50],
  ['id'=>'mani-shellac-ab','cat'=>'Nägel','name'=>"CND Shellac entfernen",'minutes'=>20,'price'=>15],
  ['id'=>'gel-ab','cat'=>'Nägel','name'=>"Gel entfernen inklusive Nagelpflege",'minutes'=>30,'price'=>25],
  ['id'=>'nagel-rep','cat'=>'Nägel','name'=>"Nagelreparatur pro Nagel",'minutes'=>15,'price'=>7],
  ['id'=>'nagel-french','cat'=>'Nägel','name'=>"French / Babyboomer",'minutes'=>15,'price'=>10],
  ['id'=>'nagel-design','cat'=>'Nägel','name'=>"Einfaches Nageldesign",'minutes'=>20,'price'=>null],
  ['id'=>'pedi','cat'=>'Fußpflege','name'=>"Klassische Pediküre",'minutes'=>45,'price'=>45],
  ['id'=>'pedi-lack','cat'=>'Fußpflege','name'=>"Pediküre mit Nagellack",'minutes'=>55,'price'=>49],
  ['id'=>'pedi-shellac','cat'=>'Fußpflege','name'=>"Pediküre mit CND Shellac",'minutes'=>60,'price'=>57],
  ['id'=>'fuss-med','cat'=>'Fußpflege','name'=>"Med. Fußpflege",'minutes'=>60,'price'=>60],
  ['id'=>'pedi-shellac-ab','cat'=>'Fußpflege','name'=>"CND Shellac entfernen",'minutes'=>20,'price'=>15],
  ['id'=>'fuss-peeling','cat'=>'Fußpflege','name'=>"Fußpeeling",'minutes'=>10,'price'=>10],
  ['id'=>'fuss-maske','cat'=>'Fußpflege','name'=>"Pflegende Fußmaske",'minutes'=>15,'price'=>12],
  ['id'=>'pedi-french','cat'=>'Fußpflege','name'=>"French mit CND Shellac",'minutes'=>15,'price'=>10],
  ['id'=>'lash-lift','cat'=>'Wimpern & Augenbrauen','name'=>"Klassisches Wimpernlifting",'minutes'=>60,'price'=>59],
  ['id'=>'lash-lift-kr','cat'=>'Wimpern & Augenbrauen','name'=>"Koreanisches Lashlifting",'minutes'=>75,'price'=>69],
  ['id'=>'brow-lift','cat'=>'Wimpern & Augenbrauen','name'=>"Augenbrauenlifting",'minutes'=>60,'price'=>59],
  ['id'=>'brow-henna','cat'=>'Wimpern & Augenbrauen','name'=>"Henna Brows",'minutes'=>20,'price'=>15],
  ['id'=>'brow-zupfen','cat'=>'Wimpern & Augenbrauen','name'=>"Augenbrauen zupfen",'minutes'=>15,'price'=>15],
  ['id'=>'lash-farbe','cat'=>'Wimpern & Augenbrauen','name'=>"Wimpern färben",'minutes'=>20,'price'=>15],
  ['id'=>'kombi-lift','cat'=>'Wimpern & Augenbrauen','name'=>"Wimpernlifting + Augenbrauenlifting",'minutes'=>120,'price'=>105],
  ['id'=>'kombi-lift-kr','cat'=>'Wimpern & Augenbrauen','name'=>"Koreanisches Lashlifting + Augenbrauenlifting",'minutes'=>135,'price'=>115],
  ['id'=>'kombi-henna','cat'=>'Wimpern & Augenbrauen','name'=>"Augenbrauen zupfen + Henna Brows",'minutes'=>30,'price'=>25],
  ['id'=>'kombi-farbe','cat'=>'Wimpern & Augenbrauen','name'=>"Wimpern färben + Augenbrauen zupfen",'minutes'=>35,'price'=>25],
  ['id'=>'ext-11','cat'=>'Wimpern & Augenbrauen','name'=>"1:1-Technik – Neuset",'minutes'=>120,'price'=>89],
  ['id'=>'ext-soft','cat'=>'Wimpern & Augenbrauen','name'=>"Soft Volume 2D–3D – Neuset",'minutes'=>150,'price'=>109],
  ['id'=>'ext-mega','cat'=>'Wimpern & Augenbrauen','name'=>"Mega Volume 4D–6D – Neuset",'minutes'=>180,'price'=>129],
  ['id'=>'ext-11-auf2','cat'=>'Wimpern & Augenbrauen','name'=>"1:1-Technik auffüllen (2 Wochen)",'minutes'=>60,'price'=>40],
  ['id'=>'ext-11-auf3','cat'=>'Wimpern & Augenbrauen','name'=>"1:1-Technik auffüllen (3 Wochen)",'minutes'=>75,'price'=>50],
  ['id'=>'ext-11-auf4','cat'=>'Wimpern & Augenbrauen','name'=>"1:1-Technik auffüllen (4 Wochen)",'minutes'=>90,'price'=>60],
  ['id'=>'ext-soft-auf2','cat'=>'Wimpern & Augenbrauen','name'=>"Soft Volume auffüllen (2 Wochen)",'minutes'=>75,'price'=>50],
  ['id'=>'ext-soft-auf3','cat'=>'Wimpern & Augenbrauen','name'=>"Soft Volume auffüllen (3 Wochen)",'minutes'=>90,'price'=>60],
  ['id'=>'ext-soft-auf4','cat'=>'Wimpern & Augenbrauen','name'=>"Soft Volume auffüllen (4 Wochen)",'minutes'=>105,'price'=>70],
  ['id'=>'ext-mega-auf2','cat'=>'Wimpern & Augenbrauen','name'=>"Mega Volume auffüllen (2 Wochen)",'minutes'=>90,'price'=>60],
  ['id'=>'ext-mega-auf3','cat'=>'Wimpern & Augenbrauen','name'=>"Mega Volume auffüllen (3 Wochen)",'minutes'=>105,'price'=>70],
  ['id'=>'ext-mega-auf4','cat'=>'Wimpern & Augenbrauen','name'=>"Mega Volume auffüllen (4 Wochen)",'minutes'=>120,'price'=>80],
  ['id'=>'ext-ab','cat'=>'Wimpern & Augenbrauen','name'=>"Wimpernverlängerung entfernen",'minutes'=>30,'price'=>25]
];

/* Gesetzliche Feiertage Baden-Württemberg: online nicht buchbar (Datum => Name) */
function holidayMap(int $y): array {
  $a = $y % 19; $b = intdiv($y, 100); $c = $y % 100; $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3);
  $hh = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $hh - $k) % 7; $m = intdiv($a + 11 * $hh + 22 * $l, 451);
  $easter = mktime(12, 0, 0, intdiv($hh + $l - 7 * $m + 114, 31), (($hh + $l - 7 * $m + 114) % 31) + 1, $y);
  $rel = fn(int $days) => date('Y-m-d', $easter + $days * 86400);
  return ["$y-01-01" => 'Neujahr', "$y-01-06" => 'Heilige Drei Könige', $rel(-2) => 'Karfreitag', $rel(1) => 'Ostermontag', "$y-05-01" => 'Tag der Arbeit',
    $rel(39) => 'Christi Himmelfahrt', $rel(50) => 'Pfingstmontag', $rel(60) => 'Fronleichnam', "$y-10-03" => 'Tag der Deutschen Einheit',
    "$y-11-01" => 'Allerheiligen', "$y-12-25" => '1. Weihnachtstag', "$y-12-26" => '2. Weihnachtstag'];
}
function holidays(int $y): array { return array_keys(holidayMap($y)); }

/* Sperrzeiten: Feiertage (außer die Inhaberin hat geöffnet) plus Urlaub/Geschlossen aus dem Studio-Kalender.
   Liefert Datum => ['kind' => feiertag|urlaub|geschlossen, 'label' => Text für die Kundinnen]. */
const CLOSURE_KINDS = ['urlaub' => 'Urlaub', 'geschlossen' => 'Geschlossen', 'feiertag' => 'Feiertag', 'sonstiges' => 'Geschlossen'];
function closedInfo(): array {
  $y = (int)date('Y'); $out = [];
  foreach ([$y, $y + 1] as $yy) foreach (holidayMap($yy) as $d => $n) $out[$d] = ['kind' => 'feiertag', 'label' => $n];
  $rows = db()->query("SELECT kind, d_from, d_to, label FROM closures WHERE d_to >= date('now', '-1 day') ORDER BY d_from")->fetchAll();
  foreach ($rows as $c) if ($c['kind'] === 'offen') unset($out[$c['d_from']]);
  foreach ($rows as $c) {
    if ($c['kind'] === 'offen') continue;
    $label = $c['kind'] === 'feiertag' ? ($c['label'] ?: 'Feiertag') : (CLOSURE_KINDS[$c['kind']] ?? 'Geschlossen');
    for ($t = strtotime($c['d_from'] . ' 12:00'), $end = strtotime($c['d_to'] . ' 12:00'), $n = 0; $t <= $end && $n < 400; $t += 86400, $n++)
      $out[date('Y-m-d', $t)] = ['kind' => $c['kind'] === 'sonstiges' ? 'geschlossen' : $c['kind'], 'label' => $label];
  }
  $today = date('Y-m-d');
  $out = array_filter($out, fn($d) => $d >= $today, ARRAY_FILTER_USE_KEY);
  ksort($out);
  return $out;
}
function closedDays(): array { return array_keys(closedInfo()); }

/* ---------- Antworten, Schutz ---------- */
function out(int $code, array $data): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function clean(string $s, int $max): string {
  $s = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '');
  return mb_substr($s, 0, $max);
}
function line(string $s, int $max): string { return clean(str_replace(["\r", "\n"], ' ', $s), $max); }
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function storeDir(): string {
  $d = __DIR__ . '/.store';
  if (!is_dir($d)) @mkdir($d, 0700, true);
  if (!file_exists($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\n");
  return $d;
}
function requireOrigin(): void {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
  $host = parse_url($origin, PHP_URL_HOST) ?: '';
  if (!in_array($host, ALLOWED_HOSTS, true)) out(403, ['ok' => false, 'error' => 'origin']);
}
function rateLimit(string $bucket, int $perHour): bool {
  $key = hash('sha256', $bucket . ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d-H'));
  $f = storeDir() . '/rl-' . substr($key, 0, 24);
  $n = (int)@file_get_contents($f);
  if ($n >= $perHour) return false;
  @file_put_contents($f, (string)($n + 1));
  if (mt_rand(1, 20) === 1) foreach (glob(storeDir() . '/rl-*') ?: [] as $x) if (filemtime($x) < time() - 7200) @unlink($x);
  return true;
}
function jsonInput(): array {
  $in = json_decode(file_get_contents('php://input') ?: '', true);
  if (!is_array($in)) out(400, ['ok' => false, 'error' => 'json']);
  return $in;
}

/* ---------- Geheimer Schlüssel für Storno- und Studio-Links (bleibt auf dem Server) ---------- */
function secret(): string {
  $f = storeDir() . '/secret.key';
  $k = @file_get_contents($f);
  if (!$k || strlen($k) < 32) { $k = bin2hex(random_bytes(32)); file_put_contents($f, $k, LOCK_EX); @chmod($f, 0600); }
  return $k;
}
function sign(string $what): string { return substr(hash_hmac('sha256', $what, secret()), 0, 32); }
function checkSig(string $what, string $sig): bool { return hash_equals(sign($what), $sig); }
function stornoUrl(string $id, string $who): string { return SITE . '/api/termine.php?a=storno&id=' . $id . '&w=' . $who . '&k=' . sign("storno:$who:$id"); }
function studioUrl(): string { return SITE . '/studio/#k=' . sign('studio'); }

/* ---------- Datenbank ---------- */
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $pdo = new PDO('sqlite:' . storeDir() . '/termine.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  $pdo->exec('PRAGMA busy_timeout = 5000; PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON;');
  $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
      id TEXT PRIMARY KEY, kind TEXT NOT NULL DEFAULT 'termin', status TEXT NOT NULL DEFAULT 'gebucht',
      date TEXT NOT NULL, start INTEGER NOT NULL, end INTEGER NOT NULL,
      services TEXT NOT NULL DEFAULT '[]', price INTEGER, name TEXT NOT NULL DEFAULT '', phone TEXT NOT NULL DEFAULT '',
      email TEXT NOT NULL DEFAULT '', note TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, cancelled_at TEXT);
    CREATE INDEX IF NOT EXISTS bookings_date ON bookings(date);
    CREATE TABLE IF NOT EXISTS slots (d TEXT NOT NULL, m INTEGER NOT NULL, booking_id TEXT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE, PRIMARY KEY (d, m));");
  $pdo->exec("CREATE TABLE IF NOT EXISTS closures (id TEXT PRIMARY KEY, kind TEXT NOT NULL, d_from TEXT NOT NULL, d_to TEXT NOT NULL, label TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS messages (id TEXT PRIMARY KEY, name TEXT NOT NULL DEFAULT '', email TEXT NOT NULL DEFAULT '', phone TEXT NOT NULL DEFAULT '',
      topic TEXT NOT NULL DEFAULT '', message TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'neu', created_at TEXT NOT NULL);");
  $cols = array_column($pdo->query('PRAGMA table_info(bookings)')->fetchAll(), 'name');
  if (!in_array('source', $cols, true)) $pdo->exec("ALTER TABLE bookings ADD COLUMN source TEXT NOT NULL DEFAULT 'online'");
  if (!in_array('seen', $cols, true)) $pdo->exec("ALTER TABLE bookings ADD COLUMN seen INTEGER NOT NULL DEFAULT 0");
  // Aufräumen: Termine 90 Tage nach dem Termin löschen, Nachrichten 90 Tage nach Eingang (läuft gelegentlich bei normalen Aufrufen mit)
  if (mt_rand(1, 10) === 1) {
    $cut = date('Y-m-d', strtotime('-' . KEEP_DAYS . ' days'));
    $pdo->prepare('DELETE FROM bookings WHERE date < ?')->execute([$cut]);
    $pdo->prepare('DELETE FROM slots WHERE d < ?')->execute([date('Y-m-d')]);
    $pdo->prepare('DELETE FROM messages WHERE created_at < ?')->execute([$cut]);
    $pdo->prepare('DELETE FROM closures WHERE d_to < ?')->execute([$cut]);
  }
  return $pdo;
}
function booking(string $id): ?array {
  $s = db()->prepare('SELECT * FROM bookings WHERE id = ?'); $s->execute([$id]);
  $b = $s->fetch(); if (!$b) return null;
  $b['services'] = json_decode($b['services'], true) ?: [];
  return $b;
}

/* ---------- Datum und Zeit ---------- */
function hm(int $m): string { return sprintf('%02d:%02d', intdiv($m, 60), $m % 60); }
function longDate(string $date): string {
  $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  $months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
  $ts = strtotime($date . ' 12:00');
  return $days[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . '. ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
function durText(int $m): string { return trim((intdiv($m, 60) ? intdiv($m, 60) . ' Std. ' : '') . ($m % 60 ? ($m % 60) . ' Min.' : '')); }
function ics(array $b): string {
  $dt = fn(int $m) => str_replace('-', '', $b['date']) . 'T' . sprintf('%02d%02d00', intdiv($m, 60), $m % 60);
  $esc = fn(string $s) => str_replace(['\\', ',', ';', "\n"], ['\\\\', '\\,', '\;', '\\n'], $s);
  return implode("\r\n", ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Wohlfuehlraum Sunny//DE', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
    'UID:' . $b['id'] . '@wohlfuehlraum-sunny.de', 'DTSTAMP:' . gmdate('Ymd\THis\Z'),
    'DTSTART;TZID=Europe/Berlin:' . $dt((int)$b['start']), 'DTEND;TZID=Europe/Berlin:' . $dt((int)$b['end']),
    'SUMMARY:' . $esc('Wohlfühlraum Sunny: ' . implode(', ', $b['services'])), 'LOCATION:' . $esc(ADDRESS),
    'DESCRIPTION:' . $esc('Absage oder Verschiebung: ' . PHONE), 'END:VEVENT', 'END:VCALENDAR']) . "\r\n";
}

/* ---------- E-Mail ---------- */
function enc(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }
function btn(string $url, string $label, bool $light = false): string {
  return '<a href="' . h($url) . '" style="display:inline-block;margin:0 8px 8px 0;' . ($light ? 'border:1px solid #B89355;color:#8A6A35;' : 'background:#2E2A27;color:#fff;')
    . 'text-decoration:none;padding:12px 20px;border-radius:4px;font-size:14px">' . h($label) . '</a>';
}
function para(string $html): string { return '<p style="font-size:15px;line-height:1.6;margin:0 0 14px">' . $html . '</p>'; }
function layout(string $title, string $bodyHtml): string {
  return '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>'
   . '<body style="margin:0;background:#F3EFEA;font-family:Helvetica,Arial,sans-serif;color:#2E2A27">'
   . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EFEA;padding:28px 12px"><tr><td align="center">'
   . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FDFBF7;border:1px solid #E6DED3">'
   . '<tr><td style="padding:26px 30px 18px;border-bottom:1px solid #E6DED3;text-align:center">'
   . '<img src="' . SITE . '/img/logo-word.png" width="220" alt="' . h(STUDIO_NAME) . '" style="display:inline-block;max-width:220px;height:auto">'
   . '<div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#B89355;margin-top:8px">Dein Ort zum Aufblühen</div></td></tr>'
   . '<tr><td style="padding:28px 30px 12px"><h1 style="font-family:Georgia,serif;font-weight:400;font-size:26px;margin:0 0 16px">' . h($title) . '</h1>' . $bodyHtml . '</td></tr>'
   . '<tr><td style="padding:22px 30px 28px;font-size:13px;line-height:1.6;color:#7A6F66;border-top:1px solid #E6DED3">'
   . h(STUDIO_NAME) . ' · ' . h(ADDRESS) . '<br>Telefon/WhatsApp ' . h(PHONE) . ' · <a href="' . SITE . '" style="color:#B89355">wohlfuehlraum-sunny.de</a></td></tr>'
   . '</table></td></tr></table></body></html>';
}
function rows(array $r): string {
  $s = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;line-height:1.5;margin:6px 0 18px">';
  foreach ($r as $k => $v) {
    if ($v === '' || $v === null) continue;
    $s .= '<tr><td style="padding:8px 12px 8px 0;color:#7A6F66;vertical-align:top;white-space:nowrap;border-bottom:1px solid #EFE8DF">' . h($k)
        . '</td><td style="padding:8px 0;border-bottom:1px solid #EFE8DF">' . nl2br(h((string)$v)) . '</td></tr>';
  }
  return $s . '</table>';
}
function textRows(array $r): string { $s = ''; foreach ($r as $k => $v) if ($v !== '' && $v !== null) $s .= $k . ': ' . $v . "\n"; return $s; }
/** @param array|null $attach ['name'=>…, 'type'=>…, 'data'=>…] */
function send(string $to, string $subject, string $text, string $html, ?string $replyTo = null, ?array $attach = null): bool {
  $b1 = 'alt-' . bin2hex(random_bytes(8));
  $alt = "--$b1\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
       . "--$b1\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "--$b1--\r\n";
  $headers = ['From: ' . enc(STUDIO_NAME) . ' <' . STUDIO_MAIL . '>', 'MIME-Version: 1.0', 'Date: ' . date('r'),
    'Message-ID: <' . bin2hex(random_bytes(12)) . '@wohlfuehlraum-sunny.de>', 'X-Mailer: wohlfuehlraum-sunny.de'];
  if ($replyTo) $headers[] = 'Reply-To: ' . $replyTo;
  if ($attach) {
    $b2 = 'mix-' . bin2hex(random_bytes(8));
    $headers[] = "Content-Type: multipart/mixed; boundary=\"$b2\"";
    $body = "--$b2\r\nContent-Type: multipart/alternative; boundary=\"$b1\"\r\n\r\n" . $alt
          . "--$b2\r\nContent-Type: {$attach['type']}; name=\"{$attach['name']}\"\r\nContent-Disposition: attachment; filename=\"{$attach['name']}\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($attach['data'])) . "--$b2--\r\n";
  } else {
    $headers[] = "Content-Type: multipart/alternative; boundary=\"$b1\"";
    $body = $alt;
  }
  return mail($to, enc($subject), $body, implode("\r\n", $headers), '-f' . STUDIO_MAIL);
}
function validEmail(string $e): bool { return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }

/* ---------- Mails an die Kundin (Online-Buchung und Studio-Kalender nutzen dieselben Vorlagen) ---------- */
function priceText(array $b): string { return $b['price'] !== null && $b['price'] !== '' ? $b['price'] . ' €' : 'nach Aufwand'; }
function mailCustomerConfirm(array $b, bool $moved = false): bool {
  if (!validEmail($b['email'])) return false;
  $name = $b['name']; $when = longDate($b['date']); $start = (int)$b['start'];
  $cr = ['Datum' => $when, 'Uhrzeit' => hm($start) . ' – ' . hm((int)$b['end']) . ' Uhr', 'Leistungen' => implode("\n", $b['services']),
         'Preis' => $b['services'] ? priceText($b) . ($b['price'] !== null ? ', zahlbar vor Ort' : '') : ''];
  $title = $moved ? 'Dein Termin wurde verschoben' : 'Dein Termin ist gebucht';
  $intro = $moved ? 'dein Termin hat eine neue Zeit. Hier sind die aktuellen Details:' : 'vielen Dank für deine Buchung. Wir freuen uns auf dich!';
  $html = layout($title,
    para(h("Hallo $name,") . '<br>' . h($intro)) . rows($cr)
    . para('<b>Adresse:</b> ' . h(ADDRESS) . '<br><a href="https://www.google.com/maps/search/?api=1&query=' . rawurlencode(ADDRESS) . '" style="color:#B89355">Route in Google Maps</a>')
    . para('Kannst du nicht kommen? Dann sag den Termin bitte rechtzeitig ab, damit jemand anderes die Zeit nutzen kann.')
    . '<p style="margin:0 0 14px">' . btn(stornoUrl($b['id'], 'c'), 'Termin absagen', true) . '</p>'
    . para('Verschieben oder Fragen: telefonisch oder per WhatsApp unter <a href="tel:' . PHONE_INTL . '" style="color:#B89355">' . h(PHONE) . '</a>.')
    . para('Den Termin findest du auch als Kalenderdatei im Anhang.<br><br>Herzliche Grüße<br>' . h(STUDIO_NAME)));
  $text = "Hallo $name,\n\n$intro\n\n" . textRows($cr) . "\nAdresse: " . ADDRESS
    . "\n\nTermin absagen: " . stornoUrl($b['id'], 'c') . "\nVerschieben oder Fragen: " . PHONE . " (Telefon/WhatsApp)\n\nHerzliche Grüße\n" . STUDIO_NAME . "\n" . SITE . "\n";
  return send($b['email'], ($moved ? 'Neue Zeit für deinen Termin: ' : 'Dein Termin am ') . $when . ' um ' . hm($start) . ' Uhr', $text, $html, STUDIO_MAIL,
    ['name' => 'termin-wohlfuehlraum-sunny.ics', 'type' => 'text/calendar; charset=UTF-8; method=PUBLISH', 'data' => ics($b)]);
}
function mailCustomerCancelledByStudio(array $b): bool {
  if (!validEmail($b['email'])) return false;
  $when = longDate($b['date']) . ', ' . hm((int)$b['start']) . ' Uhr';
  $r = ['Datum' => longDate($b['date']), 'Uhrzeit' => hm((int)$b['start']) . ' – ' . hm((int)$b['end']) . ' Uhr', 'Leistungen' => implode("\n", $b['services'])];
  return send($b['email'], 'Dein Termin am ' . $when . ' muss leider ausfallen',
    "Hallo {$b['name']},\n\nleider müssen wir deinen Termin absagen. Das tut uns leid! Bitte buch dir einen neuen Termin online (" . SITE . "/business/termin.html) oder melde dich unter " . PHONE . ".\n\nHerzliche Grüße\n" . STUDIO_NAME . "\n",
    layout('Dein Termin muss leider ausfallen', para(h("Hallo {$b['name']},") . '<br>leider müssen wir deinen Termin absagen. Das tut uns leid!') . rows($r)
      . para('Buch dir gern einen neuen Termin online oder melde dich telefonisch bzw. per WhatsApp unter ' . h(PHONE) . '.')
      . '<p>' . btn(SITE . '/business/termin.html', 'Neuen Termin buchen') . '</p>' . para('Herzliche Grüße<br>' . h(STUDIO_NAME))), STUDIO_MAIL);
}
function cancelBooking(string $id): void {
  $pdo = db();
  $pdo->exec('BEGIN IMMEDIATE');
  $pdo->prepare("UPDATE bookings SET status = 'storniert', cancelled_at = ? WHERE id = ?")->execute([date('c'), $id]);
  $pdo->prepare('DELETE FROM slots WHERE booking_id = ?')->execute([$id]);
  $pdo->exec('COMMIT');
}

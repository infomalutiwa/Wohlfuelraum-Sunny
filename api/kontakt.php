<?php
/*
 * Wohlfühlraum Sunny – E-Mail-Versand (läuft auf dem ALL-INKL-Server, nutzt PHP mail()).
 *
 *  type=contact  Kontaktformular  -> Nachricht an das Studio (Antworten gehen direkt an die Kundin)
 *  type=booking  nach Online-Buchung -> Info an das Studio + Bestätigung mit Kalenderdatei an die Kundin
 *
 * Schutz: nur POST von der eigenen Domain, Honeypot-Feld, Mindest-Ausfüllzeit, Limit pro IP.
 * Buchungs-Mails werden nur verschickt, wenn der Termin wirklich in Firestore belegt ist,
 * und pro Buchung nur einmal.
 */

const STUDIO_MAIL = 'info@wohlfuehlraum-sunny.de';
const STUDIO_NAME = 'Wohlfühlraum Sunny';
const SITE        = 'https://wohlfuehlraum-sunny.de';
const PHONE       = '0173 3098632';
const ADDRESS     = 'Müßmattstraße 22, 79618 Rheinfelden (Baden)';
const FIRESTORE   = 'https://firestore.googleapis.com/v1/projects/sunny-mails-beauty/databases/(default)/documents/slots/';
const ALLOWED_HOSTS = ['wohlfuehlraum-sunny.de', 'www.wohlfuehlraum-sunny.de', 'wohlfuehlraum-sunny.de.w022499d.kasserver.com'];
const LIMIT_PER_HOUR = 8;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out(int $code, array $data): void { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function clean(string $s, int $max): string {
  $s = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '');
  return mb_substr($s, 0, $max);
}
function line(string $s, int $max): string { return clean(str_replace(["\r", "\n"], ' ', $s), $max); }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function storeDir(): string {
  $d = __DIR__ . '/.store';
  if (!is_dir($d)) { @mkdir($d, 0700, true); @file_put_contents($d . '/.htaccess', "Require all denied\n"); }
  return $d;
}

/* --- Anfrage prüfen ----------------------------------------------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') out(204, []);
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, ['ok' => false, 'error' => 'method']);

$origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$host = parse_url($origin, PHP_URL_HOST) ?: '';
if (!in_array($host, ALLOWED_HOSTS, true)) out(403, ['ok' => false, 'error' => 'origin']);

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) out(400, ['ok' => false, 'error' => 'json']);

// Honeypot: echte Besucherinnen sehen dieses Feld nicht. Bots bekommen ein stilles "ok".
if (!empty($in['website'])) out(200, ['ok' => true]);

// Limit pro IP und Stunde
$ipKey = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d-H'));
$rl = storeDir() . '/rl-' . substr($ipKey, 0, 24);
$n = (int)@file_get_contents($rl);
if ($n >= LIMIT_PER_HOUR) out(429, ['ok' => false, 'error' => 'rate']);
@file_put_contents($rl, (string)($n + 1));
foreach (glob(storeDir() . '/rl-*') ?: [] as $f) if (filemtime($f) < time() - 7200) @unlink($f);

/* --- Mailversand -------------------------------------------------------- */
function enc(string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; }

function layout(string $title, string $bodyHtml): string {
  return '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>'
   . '<body style="margin:0;background:#F3EFEA;font-family:Helvetica,Arial,sans-serif;color:#2E2A27">'
   . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EFEA;padding:28px 12px"><tr><td align="center">'
   . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FDFBF7;border:1px solid #E6DED3">'
   . '<tr><td style="padding:26px 30px 18px;border-bottom:1px solid #E6DED3;text-align:center">'
   . '<img src="' . SITE . '/img/logo-word.png" width="220" alt="' . h(STUDIO_NAME) . '" style="display:inline-block;max-width:220px;height:auto">'
   . '<div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#B89355;margin-top:8px">Dein Ort zum Aufblühen</div></td></tr>'
   . '<tr><td style="padding:28px 30px 8px"><h1 style="font-family:Georgia,serif;font-weight:400;font-size:26px;margin:0 0 16px">' . h($title) . '</h1>' . $bodyHtml . '</td></tr>'
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
function textRows(array $r): string {
  $s = '';
  foreach ($r as $k => $v) if ($v !== '' && $v !== null) $s .= $k . ': ' . $v . "\n";
  return $s;
}

/** @param array $attach ['name'=>..., 'type'=>..., 'data'=>...] */
function send(string $to, string $subject, string $text, string $html, ?string $replyTo = null, ?array $attach = null): bool {
  $b1 = 'alt-' . bin2hex(random_bytes(8));
  $alt = "--$b1\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
       . "--$b1\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
       . "--$b1--\r\n";
  $headers = [
    'From: ' . enc(STUDIO_NAME) . ' <' . STUDIO_MAIL . '>',
    'MIME-Version: 1.0',
    'Date: ' . date('r'),
    'Message-ID: <' . bin2hex(random_bytes(12)) . '@wohlfuehlraum-sunny.de>',
    'X-Mailer: wohlfuehlraum-sunny.de',
  ];
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

$type = $in['type'] ?? '';

/* --- Kontaktformular ---------------------------------------------------- */
if ($type === 'contact') {
  $name    = line((string)($in['name'] ?? ''), 80);
  $email   = line((string)($in['email'] ?? ''), 120);
  $phone   = line((string)($in['phone'] ?? ''), 40);
  $topic   = line((string)($in['topic'] ?? ''), 60);
  $message = clean((string)($in['message'] ?? ''), 3000);
  $elapsed = (int)($in['t'] ?? 0);

  if ($elapsed < 3000) out(200, ['ok' => true]);                       // zu schnell ausgefüllt: Bot
  if ($name === '' || mb_strlen($message) < 5) out(422, ['ok' => false, 'error' => 'fields']);
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(422, ['ok' => false, 'error' => 'email']);
  if ($email === '' && strlen(preg_replace('/\D/', '', $phone)) < 6) out(422, ['ok' => false, 'error' => 'reach']);
  if (preg_match_all('~https?://~i', $message) > 2) out(422, ['ok' => false, 'error' => 'links']);

  $r = ['Name' => $name, 'E-Mail' => $email, 'Telefon' => $phone, 'Thema' => $topic];
  $html = layout('Neue Nachricht über die Website',
    rows($r) . '<div style="font-size:15px;line-height:1.6;background:#fff;border:1px solid #EFE8DF;padding:16px 18px;margin-bottom:18px">' . nl2br(h($message)) . '</div>'
    . '<p style="font-size:14px;color:#7A6F66;margin:0 0 18px">' . ($email ? 'Du kannst direkt auf diese E-Mail antworten.' : 'Keine E-Mail angegeben, bitte telefonisch melden.') . '</p>');
  $text = "Neue Nachricht über die Website\n\n" . textRows($r) . "\n" . $message . "\n";
  $ok = send(STUDIO_MAIL, 'Kontaktanfrage von ' . $name . ($topic ? ' (' . $topic . ')' : ''), $text, $html, $email ? enc($name) . " <$email>" : null);
  out($ok ? 200 : 500, ['ok' => $ok]);
}

/* --- Buchungsbestätigung ------------------------------------------------ */
if ($type === 'booking') {
  $id    = preg_replace('/[^A-Za-z0-9]/', '', (string)($in['id'] ?? ''));
  $date  = (string)($in['date'] ?? '');
  $start = (int)($in['start'] ?? -1);
  $end   = (int)($in['end'] ?? -1);
  if (strlen($id) < 10 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $start < 0 || $end <= $start || $end > 1440) out(422, ['ok' => false, 'error' => 'fields']);

  // nur einmal pro Buchung
  $done = storeDir() . '/b-' . $id;
  if (file_exists($done)) out(200, ['ok' => true, 'dup' => true]);

  // Termin muss in Firestore wirklich belegt sein (slots sind öffentlich lesbar)
  $url = FIRESTORE . rawurlencode($date . '_' . $start);
  $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false && function_exists('curl_init')) {
    $c = curl_init($url); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]); $raw = curl_exec($c); curl_close($c);
  }
  $doc = json_decode((string)$raw, true);
  if (($doc['fields']['b']['stringValue'] ?? '') !== $id) out(404, ['ok' => false, 'error' => 'booking']);
  @file_put_contents($done, date('c'));

  $name     = line((string)($in['name'] ?? ''), 80);
  $email    = line((string)($in['email'] ?? ''), 120);
  $phone    = line((string)($in['phone'] ?? ''), 40);
  $note     = clean((string)($in['note'] ?? ''), 500);
  $services = array_slice(array_map(fn($s) => line((string)$s, 90), (array)($in['services'] ?? [])), 0, 6);
  $price    = isset($in['price']) && is_numeric($in['price']) ? (int)$in['price'] : null;

  $fmt = fn(int $m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
  $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  $months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
  $ts = strtotime($date . ' 12:00');
  $when = $days[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . '. ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
  $time = $fmt($start) . ' – ' . $fmt($end) . ' Uhr';
  $dur = $end - $start;
  $durTxt = (intdiv($dur, 60) ? intdiv($dur, 60) . ' Std. ' : '') . ($dur % 60 ? ($dur % 60) . ' Min.' : '');
  $priceTxt = $price !== null ? $price . ' €' : 'nach Aufwand';

  // an das Studio
  $r = ['Datum' => $when, 'Uhrzeit' => $time, 'Leistungen' => implode("\n", $services), 'Dauer' => trim($durTxt), 'Preis' => $priceTxt,
        'Name' => $name, 'Telefon' => $phone, 'E-Mail' => $email, 'Notiz' => $note];
  send(STUDIO_MAIL, 'Neue Buchung: ' . $when . ', ' . $fmt($start) . ' Uhr – ' . $name,
    "Neue Online-Buchung\n\n" . textRows($r) . "\nAlle Termine: " . SITE . "/mail/\n",
    layout('Neue Online-Buchung', rows($r) . '<p style="margin:0 0 18px"><a href="' . SITE . '/mail/" style="display:inline-block;background:#2E2A27;color:#fff;text-decoration:none;padding:12px 22px;border-radius:4px;font-size:14px">Termine öffnen</a></p>'),
    $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? enc($name) . " <$email>" : null);

  // an die Kundin
  $sentCustomer = false;
  if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $dt = fn(int $m) => str_replace('-', '', $date) . 'T' . sprintf('%02d%02d00', intdiv($m, 60), $m % 60);
    $ics = implode("\r\n", ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Wohlfuehlraum Sunny//DE', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
      'UID:' . $id . '@wohlfuehlraum-sunny.de', 'DTSTAMP:' . gmdate('Ymd\THis\Z'),
      'DTSTART;TZID=Europe/Berlin:' . $dt($start), 'DTEND;TZID=Europe/Berlin:' . $dt($end),
      'SUMMARY:Wohlfühlraum Sunny: ' . str_replace([',', ';'], ['\\,', '\\;'], implode(', ', $services)),
      'LOCATION:Müßmattstraße 22\\, 79618 Rheinfelden (Baden)', 'DESCRIPTION:Absage oder Verschiebung bitte telefonisch: ' . PHONE,
      'END:VEVENT', 'END:VCALENDAR']) . "\r\n";
    $cr = ['Datum' => $when, 'Uhrzeit' => $time, 'Leistungen' => implode("\n", $services), 'Preis' => $priceTxt . ($price !== null ? ', zahlbar vor Ort' : '')];
    $hello = 'Hallo ' . $name . ',';
    $html = layout('Dein Termin ist gebucht',
      '<p style="font-size:15px;line-height:1.6;margin:0 0 14px">' . h($hello) . '<br>vielen Dank für deine Buchung. Wir freuen uns auf dich!</p>'
      . rows($cr)
      . '<p style="font-size:15px;line-height:1.6;margin:0 0 14px"><b>Adresse:</b> ' . h(ADDRESS) . '<br><a href="https://www.google.com/maps/search/?api=1&query=' . rawurlencode(ADDRESS) . '" style="color:#B89355">Route in Google Maps</a></p>'
      . '<p style="font-size:15px;line-height:1.6;margin:0 0 14px">Kannst du nicht kommen? Bitte sag rechtzeitig ab oder verschiebe den Termin telefonisch oder per WhatsApp unter <a href="tel:+491733098632" style="color:#B89355">' . h(PHONE) . '</a>.</p>'
      . '<p style="font-size:15px;line-height:1.6;margin:0 0 18px">Den Termin findest du auch als Kalenderdatei im Anhang.<br><br>Herzliche Grüße<br>' . h(STUDIO_NAME) . '</p>');
    $text = "$hello\n\nvielen Dank für deine Buchung. Wir freuen uns auf dich!\n\n" . textRows($cr)
      . "\nAdresse: " . ADDRESS . "\n\nKannst du nicht kommen? Bitte sag rechtzeitig ab oder verschiebe den Termin telefonisch oder per WhatsApp: " . PHONE
      . "\n\nHerzliche Grüße\n" . STUDIO_NAME . "\n" . SITE . "\n";
    $sentCustomer = send($email, 'Dein Termin am ' . $when . ' um ' . $fmt($start) . ' Uhr', $text, $html, STUDIO_MAIL,
      ['name' => 'termin-wohlfuehlraum-sunny.ics', 'type' => 'text/calendar; charset=UTF-8; method=PUBLISH', 'data' => $ics]);
  }
  out(200, ['ok' => true, 'customer' => $sentCustomer]);
}

out(400, ['ok' => false, 'error' => 'type']);

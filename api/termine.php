<?php
/*
 * Wohlfühlraum Sunny – Online-Terminbuchung auf dem eigenen Server (ALL-INKL, SQLite).
 *
 *  GET  ?a=busy&d=YYYY-MM-DD        belegte 15-Minuten-Blöcke eines Tages (ohne Namen)
 *  POST ?a=book                     Termin buchen (JSON), Mails an Studio und Kundin
 *  GET  ?a=storno&id=…&w=c|s&k=…    Storno-Seite (Kundin c / Studio s), POST storniert wirklich
 *  GET  ?a=studio&k=…               Kalender fürs Studio: Termine, stornieren, Zeiten blockieren
 *  POST ?a=block&k=…                Zeit blockieren (Urlaub, Pause, telefonischer Termin)
 *
 * Links mit k=… sind mit einem geheimen Schlüssel signiert, der nur auf dem Server liegt (api/.store/secret.key).
 */
define('SUNNY', 1);
require __DIR__ . '/_lib.php';

header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
$a = $_GET['a'] ?? '';
$post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

/* ---------------- Belegte Zeiten ---------------- */
if ($a === 'busy') {
  $d = (string)($_GET['d'] ?? '');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) out(422, ['ok' => false]);
  if (!rateLimit('busy', 600)) out(429, ['ok' => false, 'error' => 'rate']);
  $s = db()->prepare('SELECT m FROM slots WHERE d = ? ORDER BY m'); $s->execute([$d]);
  out(200, ['ok' => true, 'busy' => array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN))]);
}

/* ---------------- Buchen ---------------- */
if ($a === 'book') {
  if (!$post) out(405, ['ok' => false]);
  requireOrigin();
  $in = jsonInput();
  if (!empty($in['website'])) out(200, ['ok' => false, 'error' => 'fields']);   // Honeypot
  if (!rateLimit('book', 10)) out(429, ['ok' => false, 'error' => 'rate']);

  $date = (string)($in['date'] ?? '');
  $start = (int)($in['start'] ?? -1);
  $ids = array_values(array_unique(array_map('strval', (array)($in['services'] ?? []))));
  $name = line((string)($in['name'] ?? ''), 80);
  $phone = line((string)($in['phone'] ?? ''), 40);
  $email = line((string)($in['email'] ?? ''), 120);
  $note = clean((string)($in['note'] ?? ''), 300);

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) out(422, ['ok' => false, 'error' => 'date']);
  if ($name === '') out(422, ['ok' => false, 'error' => 'name']);
  if (strlen(preg_replace('/\D/', '', $phone)) < 6) out(422, ['ok' => false, 'error' => 'phone']);
  if ($email !== '' && !validEmail($email)) out(422, ['ok' => false, 'error' => 'email']);
  if (!$ids || count($ids) > 6) out(422, ['ok' => false, 'error' => 'services']);

  $byId = array_column(SERVICES, null, 'id');
  $chosen = [];
  foreach ($ids as $i) { if (!isset($byId[$i])) out(422, ['ok' => false, 'error' => 'services']); $chosen[] = $byId[$i]; }
  $minutes = array_sum(array_column($chosen, 'minutes'));
  $need = (int)ceil($minutes / BLOCK) * BLOCK;
  $price = in_array(null, array_column($chosen, 'price'), true) ? null : array_sum(array_column($chosen, 'price'));
  $end = $start + $need;

  // Öffnungszeiten, Raster, Vorlauf, Zeitraum
  $hours = BOOKING['hours'][(int)date('w', strtotime($date . ' 12:00'))] ?? null;
  $fits = false;
  foreach ($hours ?: [] as [$from, $to]) if ($start >= $from * 60 && $end <= $to * 60 && ($start - $from * 60) % BOOKING['slotStep'] === 0) $fits = true;
  $startTs = strtotime($date . ' 00:00') + $start * 60;
  if (!$fits || in_array($date, closedDays(), true)) out(422, ['ok' => false, 'error' => 'hours']);
  if ($startTs < time() + BOOKING['minLead'] * 60) out(422, ['ok' => false, 'error' => 'lead']);
  if ($startTs > strtotime('today') + (BOOKING['daysAhead'] + 1) * 86400) out(422, ['ok' => false, 'error' => 'ahead']);

  $id = bin2hex(random_bytes(8));
  $names = array_column($chosen, 'name');
  $pdo = db();
  try {
    $pdo->exec('BEGIN IMMEDIATE');
    $pdo->prepare('INSERT INTO bookings (id, kind, status, date, start, end, services, price, name, phone, email, note, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$id, 'termin', 'gebucht', $date, $start, $end, json_encode($names, JSON_UNESCAPED_UNICODE), $price, $name, $phone, $email, $note, date('c')]);
    $ins = $pdo->prepare('INSERT INTO slots (d, m, booking_id) VALUES (?,?,?)');
    for ($m = $start; $m < $end; $m += BLOCK) $ins->execute([$date, $m, $id]);
    $pdo->exec('COMMIT');
  } catch (PDOException $e) {
    $pdo->exec('ROLLBACK');
    if (str_contains($e->getMessage(), 'UNIQUE')) out(409, ['ok' => false, 'error' => 'taken']);
    out(500, ['ok' => false, 'error' => 'db']);
  }

  $b = booking($id);
  $priceTxt = $price !== null ? $price . ' €' : 'nach Aufwand';
  $when = longDate($date); $time = hm($start) . ' – ' . hm($end) . ' Uhr';

  // Mail ans Studio
  $r = ['Datum' => $when, 'Uhrzeit' => $time, 'Leistungen' => implode("\n", $names), 'Dauer' => durText($need), 'Preis' => $priceTxt,
        'Name' => $name, 'Telefon' => $phone, 'E-Mail' => $email, 'Notiz' => $note];
  send(STUDIO_MAIL, 'Neue Buchung: ' . $when . ', ' . hm($start) . ' Uhr – ' . $name,
    "Neue Online-Buchung\n\n" . textRows($r) . "\nTermin stornieren: " . stornoUrl($id, 's') . "\nKalender: " . studioUrl() . "\n",
    layout('Neue Online-Buchung', rows($r) . '<p style="margin:0 0 10px">' . btn(studioUrl(), 'Kalender öffnen') . btn(stornoUrl($id, 's'), 'Termin stornieren', true) . '</p>'
      . '<p style="font-size:13px;color:#7A6F66;margin:0 0 14px">Antworten auf diese E-Mail gehen direkt an die Kundin, sofern sie eine E-Mail angegeben hat.</p>'),
    validEmail($email) ? enc($name) . " <$email>" : null);

  // Bestätigung an die Kundin
  if (validEmail($email)) {
    $cr = ['Datum' => $when, 'Uhrzeit' => $time, 'Leistungen' => implode("\n", $names), 'Preis' => $priceTxt . ($price !== null ? ', zahlbar vor Ort' : '')];
    $html = layout('Dein Termin ist gebucht',
      para(h("Hallo $name,") . '<br>vielen Dank für deine Buchung. Wir freuen uns auf dich!') . rows($cr)
      . para('<b>Adresse:</b> ' . h(ADDRESS) . '<br><a href="https://www.google.com/maps/search/?api=1&query=' . rawurlencode(ADDRESS) . '" style="color:#B89355">Route in Google Maps</a>')
      . para('Kannst du nicht kommen? Dann sag den Termin bitte rechtzeitig ab, damit jemand anderes die Zeit nutzen kann.')
      . '<p style="margin:0 0 14px">' . btn(stornoUrl($id, 'c'), 'Termin absagen', true) . '</p>'
      . para('Verschieben oder Fragen: telefonisch oder per WhatsApp unter <a href="tel:' . PHONE_INTL . '" style="color:#B89355">' . h(PHONE) . '</a>.')
      . para('Den Termin findest du auch als Kalenderdatei im Anhang.<br><br>Herzliche Grüße<br>' . h(STUDIO_NAME)));
    $text = "Hallo $name,\n\nvielen Dank für deine Buchung. Wir freuen uns auf dich!\n\n" . textRows($cr) . "\nAdresse: " . ADDRESS
      . "\n\nTermin absagen: " . stornoUrl($id, 'c') . "\nVerschieben oder Fragen: " . PHONE . " (Telefon/WhatsApp)\n\nHerzliche Grüße\n" . STUDIO_NAME . "\n" . SITE . "\n";
    send($email, 'Dein Termin am ' . $when . ' um ' . hm($start) . ' Uhr', $text, $html, STUDIO_MAIL,
      ['name' => 'termin-wohlfuehlraum-sunny.ics', 'type' => 'text/calendar; charset=UTF-8; method=PUBLISH', 'data' => ics($b)]);
  }
  out(200, ['ok' => true, 'id' => $id, 'date' => $date, 'start' => $start, 'end' => $end, 'services' => $names, 'mailed' => validEmail($email)]);
}

/* ---------------- HTML-Seiten (Storno, Studio) ---------------- */
function page(string $title, string $body): void {
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: no-store');
  echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<meta name="robots" content="noindex"><title>' . h($title) . ' · ' . h(STUDIO_NAME) . '</title><link rel="icon" href="/img/favicon.png">'
    . '<style>@font-face{font-family:DM;src:url(/assets/fonts/dmserif.woff2)format("woff2")}@font-face{font-family:Ral;src:url(/assets/fonts/raleway.woff2)format("woff2");font-weight:300 700}'
    . ':root{--ink:#2E2A27;--mut:#7A6F66;--line:#E6DED3;--gold:#B89355;--paper:#FDFBF7}*{box-sizing:border-box}'
    . 'body{margin:0;background:#F3EFEA;color:var(--ink);font:400 16px/1.55 Ral,system-ui,sans-serif}main{max-width:760px;margin:0 auto;padding:28px 16px 60px}'
    . '.logo{display:block;margin:0 auto 22px;width:200px;height:auto}.card{background:var(--paper);border:1px solid var(--line);border-radius:8px;padding:clamp(18px,4vw,30px);margin-bottom:18px}'
    . 'h1,h2{font-family:DM,Georgia,serif;font-weight:400;margin:0 0 12px}h1{font-size:clamp(1.8rem,5vw,2.4rem)}h2{font-size:1.35rem}p{margin:0 0 12px}.mut{color:var(--mut);font-size:.92rem}'
    . 'dl{display:grid;grid-template-columns:auto 1fr;gap:6px 16px;margin:0 0 18px}dt{color:var(--mut)}dd{margin:0}'
    . '.btn{display:inline-block;border:0;border-radius:4px;padding:12px 20px;font:500 .95rem Ral,sans-serif;cursor:pointer;text-decoration:none;background:var(--ink);color:#fff}'
    . '.btn.line{background:transparent;color:var(--ink);border:1.5px solid var(--line)}.btn.red{background:#9b3a2e}.btn.sm{padding:7px 12px;font-size:.85rem}'
    . '.ok{border-left:4px solid #6c8f6a}.err{border-left:4px solid #9b3a2e}.day{border-top:1px solid var(--line);padding:14px 0}.day:first-of-type{border-top:0}'
    . '.row{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;padding:8px 0}.row b{display:block}.row form{flex:none}'
    . '.tag{display:inline-block;font-size:.75rem;padding:2px 8px;border-radius:3px;background:#EFE8DF;color:var(--mut);margin-left:6px}'
    . 'label{display:grid;gap:4px;font-size:.9rem;color:var(--mut)}input,select{padding:11px 12px;border:1.5px solid var(--line);border-radius:4px;background:#fff;font:400 1rem Ral,sans-serif;color:var(--ink);width:100%}'
    . '.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:14px}</style></head><body><main>'
    . '<a href="/business/"><img class="logo" src="/img/logo-word.png" alt="' . h(STUDIO_NAME) . '"></a>' . $body . '</main></body></html>';
  exit;
}
function details(array $b): string {
  return '<dl><dt>Datum</dt><dd>' . h(longDate($b['date'])) . '</dd><dt>Uhrzeit</dt><dd>' . h(hm((int)$b['start']) . ' – ' . hm((int)$b['end']) . ' Uhr') . '</dd>'
    . ($b['kind'] === 'termin' ? '<dt>Leistungen</dt><dd>' . h(implode(', ', $b['services'])) . '</dd>' : '<dt>Art</dt><dd>Blockierte Zeit</dd>')
    . ($b['name'] !== '' ? '<dt>Name</dt><dd>' . h($b['name']) . '</dd>' : '') . '</dl>';
}

/* ---------------- Stornieren ---------------- */
if ($a === 'storno') {
  $id = preg_replace('/[^a-f0-9]/', '', (string)($_GET['id'] ?? ''));
  $who = ($_GET['w'] ?? '') === 's' ? 's' : 'c';
  $k = (string)($_GET['k'] ?? '');
  $b = $id ? booking($id) : null;
  if (!$b || !checkSig("storno:$who:$id", $k)) page('Link ungültig', '<div class="card err"><h1>Dieser Link ist nicht gültig</h1><p>Der Termin wurde nicht gefunden oder ist bereits abgelaufen. Bitte melde dich telefonisch oder per WhatsApp: <a href="tel:' . PHONE_INTL . '">' . h(PHONE) . '</a>.</p></div>');
  $back = $who === 's' ? '<p><a class="btn line" href="' . h(studioUrl()) . '">Zum Kalender</a></p>' : '<p><a class="btn line" href="/business/">Zur Website</a></p>';
  $isBlock = $b['kind'] === 'block';
  if ($b['status'] === 'storniert') page('Bereits storniert', '<div class="card ok"><h1>' . ($isBlock ? 'Zeit ist wieder frei' : 'Dieser Termin ist bereits storniert') . '</h1>' . details($b) . $back . '</div>');
  $startTs = strtotime($b['date'] . ' 00:00') + (int)$b['start'] * 60;
  if ($who === 'c' && $startTs < time()) page('Nicht mehr möglich', '<div class="card err"><h1>Absage nicht mehr möglich</h1><p>Der Termin hat bereits begonnen. Bitte ruf uns an: <a href="tel:' . PHONE_INTL . '">' . h(PHONE) . '</a>.</p></div>');

  if (!$post) {
    $title = $isBlock ? 'Blockierte Zeit freigeben?' : ($who === 's' ? 'Termin stornieren?' : 'Termin absagen?');
    $hint = $isBlock ? '' : ($who === 's' ? '<p class="mut">' . (validEmail($b['email']) ? 'Die Kundin bekommt automatisch eine E-Mail, dass der Termin abgesagt wurde.' : 'Die Kundin hat keine E-Mail angegeben. Bitte informiere sie telefonisch: ' . h($b['phone']) . '.') . '</p>' : '<p class="mut">Die Zeit wird danach wieder für andere freigegeben.</p>');
    page($title, '<div class="card"><h1>' . h($title) . '</h1>' . details($b) . $hint
      . '<form method="post"><button class="btn red" type="submit">' . ($isBlock ? 'Zeit freigeben' : ($who === 's' ? 'Termin stornieren' : 'Ja, Termin absagen')) . '</button></form></div>' . $back);
  }
  requireOrigin();
  $pdo = db();
  $pdo->exec('BEGIN IMMEDIATE');
  $pdo->prepare("UPDATE bookings SET status = 'storniert', cancelled_at = ? WHERE id = ?")->execute([date('c'), $id]);
  $pdo->prepare('DELETE FROM slots WHERE booking_id = ?')->execute([$id]);
  $pdo->exec('COMMIT');

  if (!$isBlock) {
    $when = longDate($b['date']) . ', ' . hm((int)$b['start']) . ' Uhr';
    $r = ['Datum' => longDate($b['date']), 'Uhrzeit' => hm((int)$b['start']) . ' – ' . hm((int)$b['end']) . ' Uhr', 'Leistungen' => implode("\n", $b['services']), 'Name' => $b['name'], 'Telefon' => $b['phone'], 'E-Mail' => $b['email']];
    if ($who === 'c') {
      send(STUDIO_MAIL, 'Abgesagt: ' . $when . ' – ' . $b['name'], "Die Kundin hat den Termin online abgesagt. Die Zeit ist wieder frei.\n\n" . textRows($r),
        layout('Termin abgesagt', para('Die Kundin hat den Termin online abgesagt. Die Zeit ist wieder frei buchbar.') . rows($r) . '<p>' . btn(studioUrl(), 'Kalender öffnen') . '</p>'));
      if (validEmail($b['email'])) send($b['email'], 'Dein Termin am ' . $when . ' ist abgesagt',
        "Hallo {$b['name']},\n\ndein Termin ist abgesagt. Schade, dass es nicht klappt. Einen neuen Termin kannst du jederzeit online buchen: " . SITE . "/business/termin.html\n\nHerzliche Grüße\n" . STUDIO_NAME . "\n",
        layout('Dein Termin ist abgesagt', para(h("Hallo {$b['name']},") . '<br>dein Termin ist abgesagt. Schade, dass es nicht klappt.') . rows(['Datum' => $r['Datum'], 'Uhrzeit' => $r['Uhrzeit'], 'Leistungen' => $r['Leistungen']])
          . '<p>' . btn(SITE . '/business/termin.html', 'Neuen Termin buchen') . '</p>' . para('Herzliche Grüße<br>' . h(STUDIO_NAME))), STUDIO_MAIL);
    } elseif (validEmail($b['email'])) {
      send($b['email'], 'Dein Termin am ' . $when . ' muss leider ausfallen',
        "Hallo {$b['name']},\n\nleider müssen wir deinen Termin absagen. Das tut uns leid! Bitte buch dir einen neuen Termin online (" . SITE . "/business/termin.html) oder melde dich unter " . PHONE . ".\n\nHerzliche Grüße\n" . STUDIO_NAME . "\n",
        layout('Dein Termin muss leider ausfallen', para(h("Hallo {$b['name']},") . '<br>leider müssen wir deinen Termin absagen. Das tut uns leid!') . rows(['Datum' => $r['Datum'], 'Uhrzeit' => $r['Uhrzeit'], 'Leistungen' => $r['Leistungen']])
          . para('Buch dir gern einen neuen Termin online oder melde dich telefonisch bzw. per WhatsApp unter ' . h(PHONE) . '.')
          . '<p>' . btn(SITE . '/business/termin.html', 'Neuen Termin buchen') . '</p>' . para('Herzliche Grüße<br>' . h(STUDIO_NAME))), STUDIO_MAIL);
    }
  }
  if ($who === 's') { header('Location: ' . studioUrl() . '&msg=' . ($isBlock ? 'frei' : 'storniert'), true, 303); exit; }
  page('Termin abgesagt', '<div class="card ok"><h1>Dein Termin ist abgesagt</h1>' . details($b) . '<p>Danke, dass du Bescheid gegeben hast. ' . (validEmail($b['email']) ? 'Eine Bestätigung ist per E-Mail unterwegs.' : '') . '</p><p><a class="btn" href="/business/termin.html">Neuen Termin buchen</a></p></div>');
}

/* ---------------- Studio: Kalender und Zeiten blockieren ---------------- */
if ($a === 'studio' || $a === 'block') {
  if (!checkSig('studio', (string)($_GET['k'] ?? ''))) page('Link ungültig', '<div class="card err"><h1>Dieser Link ist nicht gültig</h1><p>Bitte den Link aus einer aktuellen Buchungs-E-Mail verwenden.</p></div>');
  $self = studioUrl();
  $msg = '';
  if ($a === 'block' && $post) {
    requireOrigin();
    $d = (string)($_POST['d'] ?? '');
    $from = preg_match('/^(\d{1,2}):(\d{2})$/', (string)($_POST['from'] ?? ''), $x) ? (int)$x[1] * 60 + (int)$x[2] : -1;
    $to = preg_match('/^(\d{1,2}):(\d{2})$/', (string)($_POST['to'] ?? ''), $y) ? (int)$y[1] * 60 + (int)$y[2] : -1;
    $note = line((string)($_POST['note'] ?? ''), 80);
    $allDay = !empty($_POST['all']);
    if ($allDay) { $from = 6 * 60; $to = 22 * 60; }
    $from = intdiv(max($from, 0), BLOCK) * BLOCK; $to = (int)ceil($to / BLOCK) * BLOCK;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || $from < 0 || $to <= $from || $to > 24 * 60) {
      $msg = '<div class="card err"><p>Bitte Datum sowie Von- und Bis-Zeit angeben.</p></div>';
    } else {
      $pdo = db(); $id = bin2hex(random_bytes(8));
      $pdo->exec('BEGIN IMMEDIATE');
      $pdo->prepare('INSERT INTO bookings (id, kind, status, date, start, end, note, created_at) VALUES (?,?,?,?,?,?,?,?)')->execute([$id, 'block', 'gebucht', $d, $from, $to, $note, date('c')]);
      $taken = $pdo->prepare('SELECT m FROM slots WHERE d = ? AND m >= ? AND m < ?'); $taken->execute([$d, $from, $to]);
      $busy = array_flip(array_map('intval', $taken->fetchAll(PDO::FETCH_COLUMN)));
      $ins = $pdo->prepare('INSERT INTO slots (d, m, booking_id) VALUES (?,?,?)');
      $n = 0; for ($m = $from; $m < $to; $m += BLOCK) if (!isset($busy[$m])) { $ins->execute([$d, $m, $id]); $n++; }
      if ($n === 0) { $pdo->exec('ROLLBACK'); $msg = '<div class="card err"><p>Diese Zeit ist bereits komplett belegt.</p></div>'; }
      else { $pdo->exec('COMMIT'); header('Location: ' . $self . '&msg=blockiert', true, 303); exit; }
    }
  }
  $flash = ['storniert' => 'Der Termin ist storniert, die Zeit ist wieder frei.', 'frei' => 'Die Zeit ist wieder freigegeben.', 'blockiert' => 'Die Zeit ist blockiert und online nicht mehr buchbar.'][$_GET['msg'] ?? ''] ?? '';
  if ($flash) $msg = '<div class="card ok"><p style="margin:0">' . h($flash) . '</p></div>';

  $s = db()->prepare("SELECT * FROM bookings WHERE status = 'gebucht' AND date >= ? ORDER BY date, start");
  $s->execute([date('Y-m-d')]);
  $byDay = [];
  foreach ($s->fetchAll() as $b) { $b['services'] = json_decode($b['services'], true) ?: []; $byDay[$b['date']][] = $b; }
  $list = '';
  foreach ($byDay as $d => $items) {
    $list .= '<div class="day"><h2>' . h(longDate($d)) . '</h2>';
    foreach ($items as $b) {
      $isBlock = $b['kind'] === 'block';
      $tel = preg_replace('/[^\d+]/', '', $b['phone']);
      $list .= '<div class="row"><div><b>' . h(hm((int)$b['start']) . ' – ' . hm((int)$b['end'])) . ($isBlock ? '<span class="tag">blockiert</span>' : '') . '</b>'
        . ($isBlock ? h($b['note'] ?: 'Blockierte Zeit')
          : h($b['name']) . ' · ' . h(implode(', ', $b['services'])) . '<br><span class="mut">' . ($tel ? '<a href="tel:' . h($tel) . '">' . h($b['phone']) . '</a>' : '') . ($b['email'] ? ' · <a href="mailto:' . h($b['email']) . '">' . h($b['email']) . '</a>' : '') . ($b['note'] ? ' · ' . h($b['note']) : '') . '</span>')
        . '</div><a class="btn line sm" href="' . h(stornoUrl($b['id'], 's')) . '">' . ($isBlock ? 'Freigeben' : 'Stornieren') . '</a></div>';
    }
    $list .= '</div>';
  }
  if (!$list) $list = '<p class="mut">Keine kommenden Termine.</p>';
  page('Kalender', $msg
    . '<div class="card"><h1>Kalender</h1><p class="mut">Alle kommenden Online-Termine und blockierten Zeiten. Diesen Link nicht weitergeben, am besten als Lesezeichen speichern.</p>' . $list . '</div>'
    . '<div class="card"><h2>Zeit blockieren</h2><p class="mut">Für Urlaub, Pausen oder telefonisch vereinbarte Termine. Die Zeit ist danach online nicht mehr buchbar.</p>'
    . '<form method="post" action="' . h($self . '&a=block') . '"><div class="grid"><label>Datum<input type="date" name="d" required min="' . date('Y-m-d') . '"></label>'
    . '<label>Von<input type="time" name="from" step="900" value="08:00"></label><label>Bis<input type="time" name="to" step="900" value="13:00"></label></div>'
    . '<label style="margin-bottom:10px">Notiz (z. B. Urlaub, Frau Müller telefonisch)<input type="text" name="note" maxlength="80"></label>'
    . '<label style="display:flex;gap:8px;align-items:center;margin-bottom:14px"><input type="checkbox" name="all" value="1" style="width:auto"> Ganzen Tag blockieren</label>'
    . '<button class="btn" type="submit">Zeit blockieren</button></form></div>');
}

out(400, ['ok' => false, 'error' => 'action']);

<?php
/*
 * Wohlfühlraum Sunny – Schnittstelle für den Studio-Kalender (/studio/).
 * Zugang nur mit dem geheimen Studio-Schlüssel (Header X-Studio-Key), den der Link in jeder Buchungs-Mail enthält.
 *
 *  GET  ?a=data&from=…&to=…   Termine, Sperrzeiten, Feiertage, Öffnungszeiten, Leistungen
 *  GET  ?a=closures           alle kommenden Sperrzeiten und Feiertage (12 Monate)
 *  GET  ?a=inbox              Eingang: neue Online-Buchungen, Absagen, Nachrichten aus dem Kontaktformular
 *  POST ?a=save               Termin oder Blocker anlegen/ändern
 *  POST ?a=cancel             Termin stornieren (optional Mail an die Kundin) bzw. Blocker löschen
 *  POST ?a=closure            Sperrtage anlegen/ändern (Urlaub, Geschlossen, Feiertag, Sonstiges)
 *  POST ?a=closure_del        Sperrtage löschen
 *  POST ?a=holiday            gesetzlichen Feiertag öffnen oder wieder schließen
 *  POST ?a=msg                Nachricht als gelesen/erledigt markieren oder löschen
 *  POST ?a=seen               Eingangs-Buchungen als gesehen markieren
 */
define('SUNNY', 1);
require __DIR__ . '/_lib.php';
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

$key = (string)($_SERVER['HTTP_X_STUDIO_KEY'] ?? '');
if ($key === '' || !checkSig('studio', $key)) {
  rateLimit('studio-auth', 30);
  out(403, ['ok' => false, 'error' => 'key']);
}
$a = $_GET['a'] ?? '';
$post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
if ($post) requireOrigin();

const DATE_RE = '/^\d{4}-\d{2}-\d{2}$/';
function validDate(string $d): bool { return (bool)preg_match(DATE_RE, $d) && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4)); }
function ev(array $b): array {
  $sv = is_array($b['services']) ? $b['services'] : (json_decode($b['services'], true) ?: []);
  return ['id' => $b['id'], 'kind' => $b['kind'], 'source' => $b['source'] ?? 'online', 'status' => $b['status'], 'date' => $b['date'],
    'start' => (int)$b['start'], 'end' => (int)$b['end'], 'services' => $sv, 'price' => $b['price'] === null ? null : (int)$b['price'],
    'name' => $b['name'], 'phone' => $b['phone'], 'email' => $b['email'], 'note' => $b['note'], 'seen' => (int)($b['seen'] ?? 1),
    'created' => $b['created_at'], 'cancelled' => $b['cancelled_at']];
}
function inboxCount(): int {
  $n = (int)db()->query("SELECT COUNT(*) FROM bookings WHERE seen = 0 AND kind = 'termin'")->fetchColumn();
  return $n + (int)db()->query("SELECT COUNT(*) FROM messages WHERE status = 'neu'")->fetchColumn();
}

/* ---------------- Lesen ---------------- */
if ($a === 'data') {
  $from = (string)($_GET['from'] ?? ''); $to = (string)($_GET['to'] ?? '');
  if (!validDate($from) || !validDate($to) || $to < $from || strtotime($to) - strtotime($from) > 120 * 86400) out(422, ['ok' => false, 'error' => 'range']);
  $s = db()->prepare("SELECT * FROM bookings WHERE status = 'gebucht' AND date >= ? AND date <= ? ORDER BY date, start");
  $s->execute([$from, $to]);
  $events = array_map('ev', $s->fetchAll());
  $c = db()->prepare('SELECT id, kind, d_from AS "from", d_to AS "to", label FROM closures WHERE d_to >= ? AND d_from <= ? ORDER BY d_from');
  $c->execute([$from, $to]);
  $closures = $c->fetchAll();
  $opened = []; foreach ($closures as $x) if ($x['kind'] === 'offen') $opened[$x['from']] = true;
  $hol = [];
  for ($y = (int)substr($from, 0, 4); $y <= (int)substr($to, 0, 4); $y++)
    foreach (holidayMap($y) as $d => $n) if ($d >= $from && $d <= $to) $hol[] = ['date' => $d, 'name' => $n, 'open' => isset($opened[$d])];
  out(200, ['ok' => true, 'today' => date('Y-m-d'), 'now' => (int)date('G') * 60 + (int)date('i'), 'hours' => BOOKING['hours'],
    'services' => SERVICES, 'events' => $events, 'closures' => array_values(array_filter($closures, fn($x) => $x['kind'] !== 'offen')),
    'holidays' => $hol, 'inbox' => inboxCount()]);
}

if ($a === 'closures') {
  $today = date('Y-m-d'); $until = date('Y-m-d', strtotime('+400 days'));
  $c = db()->prepare('SELECT id, kind, d_from AS "from", d_to AS "to", label FROM closures WHERE d_to >= ? ORDER BY d_from');
  $c->execute([$today]);
  $rows = $c->fetchAll();
  $opened = []; foreach ($rows as $x) if ($x['kind'] === 'offen') $opened[$x['from']] = true;
  $hol = [];
  foreach ([(int)date('Y'), (int)date('Y') + 1] as $y) foreach (holidayMap($y) as $d => $n) if ($d >= $today && $d <= $until) $hol[] = ['date' => $d, 'name' => $n, 'open' => isset($opened[$d])];
  out(200, ['ok' => true, 'closures' => array_values(array_filter($rows, fn($x) => $x['kind'] !== 'offen')), 'holidays' => $hol]);
}

if ($a === 'inbox') {
  $m = db()->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 200")->fetchAll();
  $b = db()->query("SELECT * FROM bookings WHERE kind = 'termin' AND (seen = 0 OR (source = 'online' AND created_at >= '" . date('Y-m-d', strtotime('-14 days')) . "'))
    ORDER BY COALESCE(cancelled_at, created_at) DESC LIMIT 200")->fetchAll();
  out(200, ['ok' => true, 'messages' => $m, 'bookings' => array_map('ev', $b), 'inbox' => inboxCount()]);
}

if (!$post) out(405, ['ok' => false]);
$in = jsonInput();

/* ---------------- Termin oder Blocker speichern ---------------- */
if ($a === 'save') {
  $id = preg_replace('/[^a-f0-9]/', '', (string)($in['id'] ?? ''));
  $kind = ($in['kind'] ?? '') === 'block' ? 'block' : 'termin';
  $date = (string)($in['date'] ?? '');
  $start = intdiv(max(0, (int)($in['start'] ?? -1)), BLOCK) * BLOCK;
  $end = (int)ceil(min(24 * 60, (int)($in['end'] ?? 0)) / BLOCK) * BLOCK;
  $name = line((string)($in['name'] ?? ''), 80);
  $phone = line((string)($in['phone'] ?? ''), 40);
  $email = line((string)($in['email'] ?? ''), 120);
  $note = clean((string)($in['note'] ?? ''), 300);
  $custom = line((string)($in['custom'] ?? ''), 80);
  $notify = !empty($in['notify']);
  $force = !empty($in['force']);

  if (!validDate($date)) out(422, ['ok' => false, 'error' => 'date']);
  if ($end <= $start) out(422, ['ok' => false, 'error' => 'time']);
  if ($kind === 'termin' && $name === '') out(422, ['ok' => false, 'error' => 'name']);
  if ($email !== '' && !validEmail($email)) out(422, ['ok' => false, 'error' => 'email']);

  $byId = array_column(SERVICES, null, 'id'); $names = []; $prices = [];
  if ($kind === 'termin') {
    foreach (array_unique(array_map('strval', (array)($in['services'] ?? []))) as $sid) {
      if (!isset($byId[$sid])) out(422, ['ok' => false, 'error' => 'services']);
      $names[] = $byId[$sid]['name']; $prices[] = $byId[$sid]['price'];
    }
    if ($custom !== '') { $names[] = $custom; $prices[] = null; }
    if (!$names) out(422, ['ok' => false, 'error' => 'services']);
    // Bestehende Leistungen beim Bearbeiten behalten, wenn nichts Neues gewählt wurde, übernimmt die Oberfläche
  }
  $price = $kind === 'termin' && !in_array(null, $prices, true) ? array_sum($prices) : null;
  if ($kind === 'termin' && isset($in['price']) && $in['price'] !== '' && $in['price'] !== null) $price = max(0, min(9999, (int)$in['price']));

  $pdo = db();
  $old = $id ? booking($id) : null;
  if ($id && (!$old || $old['status'] !== 'gebucht')) out(404, ['ok' => false, 'error' => 'gone']);
  $pdo->exec('BEGIN IMMEDIATE');
  try {
    if ($old) $pdo->prepare('DELETE FROM slots WHERE booking_id = ?')->execute([$id]);
    $q = $pdo->prepare('SELECT DISTINCT b.id, b.kind, b.name, b.note, b.start, b.end FROM slots s JOIN bookings b ON b.id = s.booking_id WHERE s.d = ? AND s.m >= ? AND s.m < ?');
    $q->execute([$date, $start, $end]);
    $conflicts = $q->fetchAll();
    if ($conflicts && !$force) {
      $pdo->exec('ROLLBACK');
      out(409, ['ok' => false, 'error' => 'conflict', 'conflicts' => array_map(fn($c) => ['kind' => $c['kind'], 'name' => $c['kind'] === 'block' ? ($c['note'] ?: 'Blockiert') : $c['name'],
        'start' => (int)$c['start'], 'end' => (int)$c['end']], $conflicts)]);
    }
    if ($old) {
      $pdo->prepare('UPDATE bookings SET kind = ?, date = ?, start = ?, end = ?, services = ?, price = ?, name = ?, phone = ?, email = ?, note = ? WHERE id = ?')
          ->execute([$kind, $date, $start, $end, json_encode($names, JSON_UNESCAPED_UNICODE), $price, $name, $phone, $email, $note, $id]);
    } else {
      $id = bin2hex(random_bytes(8));
      $pdo->prepare('INSERT INTO bookings (id, kind, status, date, start, end, services, price, name, phone, email, note, created_at, source, seen) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)')
          ->execute([$id, $kind, 'gebucht', $date, $start, $end, json_encode($names, JSON_UNESCAPED_UNICODE), $price, $name, $phone, $email, $note, date('c'), 'studio']);
    }
    $ins = $pdo->prepare('INSERT OR IGNORE INTO slots (d, m, booking_id) VALUES (?,?,?)');
    for ($m = $start; $m < $end; $m += BLOCK) $ins->execute([$date, $m, $id]);
    $pdo->exec('COMMIT');
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->exec('ROLLBACK');
    out(500, ['ok' => false, 'error' => 'db']);
  }
  $b = booking($id);
  $mailed = false;
  if ($kind === 'termin' && $notify && validEmail($email)) {
    $moved = $old && ($old['date'] !== $date || (int)$old['start'] !== $start);
    if (!$old || $moved) $mailed = mailCustomerConfirm($b, (bool)$old);
  }
  out(200, ['ok' => true, 'event' => ev($b), 'mailed' => $mailed]);
}

/* ---------------- Stornieren / Blocker löschen ---------------- */
if ($a === 'cancel') {
  $id = preg_replace('/[^a-f0-9]/', '', (string)($in['id'] ?? ''));
  $b = $id ? booking($id) : null;
  if (!$b) out(404, ['ok' => false, 'error' => 'gone']);
  if ($b['status'] === 'gebucht') cancelBooking($id);
  $mailed = $b['kind'] === 'termin' && !empty($in['notify']) && $b['status'] === 'gebucht' ? mailCustomerCancelledByStudio($b) : false;
  if ($b['kind'] === 'block') db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
  out(200, ['ok' => true, 'mailed' => $mailed]);
}

/* ---------------- Sperrtage ---------------- */
if ($a === 'closure') {
  $id = preg_replace('/[^a-f0-9]/', '', (string)($in['id'] ?? ''));
  $kind = (string)($in['kind'] ?? '');
  $from = (string)($in['from'] ?? ''); $to = (string)($in['to'] ?? $from);
  $label = line((string)($in['label'] ?? ''), 60);
  if (!isset(CLOSURE_KINDS[$kind])) out(422, ['ok' => false, 'error' => 'kind']);
  if (!validDate($from) || !validDate($to) || $to < $from) out(422, ['ok' => false, 'error' => 'date']);
  if (strtotime($to) - strtotime($from) > 366 * 86400) out(422, ['ok' => false, 'error' => 'long']);
  if ($id) db()->prepare('UPDATE closures SET kind = ?, d_from = ?, d_to = ?, label = ? WHERE id = ?')->execute([$kind, $from, $to, $label, $id]);
  else db()->prepare('INSERT INTO closures (id, kind, d_from, d_to, label, created_at) VALUES (?,?,?,?,?,?)')->execute([$id = bin2hex(random_bytes(8)), $kind, $from, $to, $label, date('c')]);
  $s = db()->prepare("SELECT COUNT(*) FROM bookings WHERE kind = 'termin' AND status = 'gebucht' AND date >= ? AND date <= ?");
  $s->execute([$from, $to]);
  out(200, ['ok' => true, 'id' => $id, 'affected' => (int)$s->fetchColumn()]);
}
if ($a === 'closure_del') {
  db()->prepare("DELETE FROM closures WHERE id = ? AND kind <> 'offen'")->execute([preg_replace('/[^a-f0-9]/', '', (string)($in['id'] ?? ''))]);
  out(200, ['ok' => true]);
}
if ($a === 'holiday') {
  $d = (string)($in['date'] ?? '');
  if (!validDate($d) || !isset(holidayMap((int)substr($d, 0, 4))[$d])) out(422, ['ok' => false, 'error' => 'date']);
  db()->prepare("DELETE FROM closures WHERE kind = 'offen' AND d_from = ?")->execute([$d]);
  if (!empty($in['open'])) db()->prepare("INSERT INTO closures (id, kind, d_from, d_to, label, created_at) VALUES (?, 'offen', ?, ?, '', ?)")->execute([bin2hex(random_bytes(8)), $d, $d, date('c')]);
  out(200, ['ok' => true]);
}

/* ---------------- Eingang ---------------- */
if ($a === 'msg') {
  $id = preg_replace('/[^a-f0-9]/', '', (string)($in['id'] ?? ''));
  $st = (string)($in['status'] ?? '');
  if ($st === 'delete') db()->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
  elseif (in_array($st, ['neu', 'gelesen', 'erledigt'], true)) db()->prepare('UPDATE messages SET status = ? WHERE id = ?')->execute([$st, $id]);
  else out(422, ['ok' => false, 'error' => 'status']);
  out(200, ['ok' => true, 'inbox' => inboxCount()]);
}
if ($a === 'seen') {
  if (!empty($in['all'])) db()->exec('UPDATE bookings SET seen = 1 WHERE seen = 0');
  else {
    $s = db()->prepare('UPDATE bookings SET seen = 1 WHERE id = ?');
    foreach (array_slice((array)($in['ids'] ?? []), 0, 200) as $i) $s->execute([preg_replace('/[^a-f0-9]/', '', (string)$i)]);
  }
  out(200, ['ok' => true, 'inbox' => inboxCount()]);
}

out(400, ['ok' => false, 'error' => 'action']);

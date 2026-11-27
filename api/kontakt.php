<?php
/*
 * Wohlfühlraum Sunny – Kontaktformular: schickt die Nachricht per E-Mail an das Studio
 * (Antworten gehen direkt an die Absenderin). Schutz: eigene Domain, Honeypot, Mindest-Ausfüllzeit, Limit pro IP.
 */
define('SUNNY', 1);
require __DIR__ . '/_lib.php';
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, ['ok' => false, 'error' => 'method']);
requireOrigin();
$in = jsonInput();
if (!empty($in['website'])) out(200, ['ok' => true]);                   // Honeypot: Bots bekommen ein stilles "ok"
if (($in['type'] ?? '') !== 'contact') out(400, ['ok' => false, 'error' => 'type']);
if (!rateLimit('contact', 8)) out(429, ['ok' => false, 'error' => 'rate']);

$name    = line((string)($in['name'] ?? ''), 80);
$email   = line((string)($in['email'] ?? ''), 120);
$phone   = line((string)($in['phone'] ?? ''), 40);
$topic   = line((string)($in['topic'] ?? ''), 60);
$message = clean((string)($in['message'] ?? ''), 3000);

if ((int)($in['t'] ?? 0) < 3000) out(422, ['ok' => false, 'error' => 'fast']); // in unter 3 Sekunden ausgefüllt: vermutlich Bot
if ($name === '' || mb_strlen($message) < 5) out(422, ['ok' => false, 'error' => 'fields']);
if ($email !== '' && !validEmail($email)) out(422, ['ok' => false, 'error' => 'email']);
if ($email === '' && strlen(preg_replace('/\D/', '', $phone)) < 6) out(422, ['ok' => false, 'error' => 'reach']);
if (preg_match_all('~https?://~i', $message) > 2) out(422, ['ok' => false, 'error' => 'links']);

// Zusätzlich im Studio-Kalender unter „Eingang“ ablegen (wird nach 90 Tagen gelöscht)
try {
  db()->prepare('INSERT INTO messages (id, name, email, phone, topic, message, created_at) VALUES (?,?,?,?,?,?,?)')
      ->execute([bin2hex(random_bytes(8)), $name, $email, $phone, $topic, $message, date('c')]);
} catch (Throwable $e) { /* Mail geht trotzdem raus */ }

$r = ['Name' => $name, 'E-Mail' => $email, 'Telefon' => $phone, 'Thema' => $topic];
$html = layout('Neue Nachricht über die Website',
  rows($r) . '<div style="font-size:15px;line-height:1.6;background:#fff;border:1px solid #EFE8DF;padding:16px 18px;margin-bottom:18px">' . nl2br(h($message)) . '</div>'
  . '<p style="font-size:14px;color:#7A6F66;margin:0 0 18px">' . ($email ? 'Du kannst direkt auf diese E-Mail antworten.' : 'Keine E-Mail angegeben, bitte telefonisch melden.') . '</p>'
  . '<p style="margin:0 0 10px">' . btn(studioUrl(), 'Im Studio-Kalender öffnen') . '</p>');
$ok = send(STUDIO_MAIL, 'Kontaktanfrage von ' . $name . ($topic ? ' (' . $topic . ')' : ''), "Neue Nachricht über die Website\n\n" . textRows($r) . "\n" . $message . "\n", $html, $email ? enc($name) . " <$email>" : null);
out($ok ? 200 : 500, ['ok' => $ok]);

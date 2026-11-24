<?php
/* Liefert Öffnungszeiten und Leistungen für die Buchungsseite (eine Quelle: api/_lib.php). */
define('SUNNY', 1);
require __DIR__ . '/_lib.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache');
$cfg = BOOKING + ['api' => '/api/termine.php', 'mailApi' => '/api/kontakt.php', 'whatsapp' => '491733098632', 'services' => SERVICES, 'closed' => closedDays()];
echo 'window.SUNNY_BOOKING=' . json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";

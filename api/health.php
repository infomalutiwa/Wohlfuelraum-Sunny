<?php
header('Content-Type: application/json');
echo json_encode(['php'=>PHP_VERSION,'sqlite'=>in_array('sqlite',PDO::getAvailableDrivers(),true),'mysql'=>in_array('mysql',PDO::getAvailableDrivers(),true),'tz'=>date_default_timezone_get(),'mail'=>function_exists('mail')]);

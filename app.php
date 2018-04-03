<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/vendor/autoload.php';

require __DIR__ . '/src/class/class.tpl.php';
require __DIR__ . '/src/class/class.instagram.php';
session_start();

// Load .env file
if(file_exists(__DIR__ . '/.env')) {
  $rows = explode("\n", file_get_contents(__DIR__ . '/.env'));
  foreach($rows as $row) {
      list($k, $v) = explode("=", $row, 2);
      define($k, trim($v, '"'));
  }
}
// Load environement variables
foreach($_ENV as $k => $v) {
  define($k, $v);
}

// Get controller
if(!$controller = basename($_SERVER['REDIRECT_URL'])) {
  $controller  = 'index.php';
} else {
  $controller .= '.php';
}

class RaisedError extends Exception {}

if(file_exists(__DIR__ . '/src/controllers/' . $controller)) {
  try {
    include __DIR__ . '/src/controllers/' . $controller;
  } catch(RaisedError $e) {
    print $tpl->error(['error' => $e->getMessage()]);
    die;
  }
} else {
  header("HTTP/1.0 404 Not Found");
  exit();
}
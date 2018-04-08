<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/class/class.tpl.php';
require __DIR__ . '/src/class/class.instagram.php';
require __DIR__ . '/fct.common.php';

ini_set('session.gc_maxlifetime', 3600); // server should keep session data for AT LEAST 1 hour
session_set_cookie_params(3600); // each client should remember their session id for EXACTLY 1 hour
session_start();

// Get controller
$path = trim($_SERVER['REDIRECT_URL'], '/');

if(!$path) {
  $controller = 'index.php';
} else {
  $parts      = explode('/', $path, 2);
  $controller = $parts[0] . '.php';
  $PATH       = @$parts[1];
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
  echo "404";
  exit();
}
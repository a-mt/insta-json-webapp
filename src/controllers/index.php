<?php
if(!isLoggedin()) {
  unset($_SESSION['user']);

  header('Location: /login');
  exit;
}

// Retrieve user info
if(!isset($_SESSION['username']['fullName'])) {
  if(!$user = $ig->getAccount($_SESSION['user']['username'])) {
    unset($_SESSION['user']);

    header('Location: /login');
    exit;
  }

  $m = array();
  foreach(get_class_methods($user) as $method) {
    if($method == "getColumns" || !preg_match('/^(?:get|is)/', $method, $m)) {
      continue;
    }
    $name = lcfirst(substr($method, strlen($m[0])));
    $_SESSION['user'][$name] = $user->$method();
  }
}

// Retrieve user feed
print $tpl->index();
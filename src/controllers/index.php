<?php
if(!isset($_SESSION['user'])) {
  header('Location: /login');
  exit;
}

// Get session from cache
try {
  $ig = Instagram::withCredentials($_SESSION['user']['username'], "-");
  $ig->login();

} catch(\InstagramScraper\Exception\InstagramAuthException $e) {
  unset($_SESSION['user']);

  header('Location: /login');
  exit;
}

print $tpl->index();
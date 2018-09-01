<?php
if(isset($_SESSION['user'])) {
  header('Location: ' . REWRITE_BASE);
  exit;
}

// POST Step 1: username + password
if(isset($_POST['username'])) {
  postLogin($_POST['username'], $_POST['password']);
  loggedin();
}

// POST Step 2: Choose challenge (send mail/sms)
if(isset($_POST['choice']) && isset($_SESSION['challenge'])) {
  if($challenge = $ig->postChallenge(
    $_SESSION['challenge']['url'],
    $_SESSION['challenge']['headers'],
    $_POST['choice'])
  ) {
    formChallenge($challenge['entry_data']['Challenge'][0]);
  }
}

// POST Step 3 : Enter security code
if(isset($_POST['security_code']) && isset($_SESSION['challenge'])) {
  if($challenge = $ig->postSecurityCode(
      $_SESSION['username'],
      $_SESSION['challenge']['url'],
      $_SESSION['challenge']['headers'],
      $_POST['security_code'])
  ) {
    // Code expired / wrong security code
    formChallenge($challenge['entry_data']['Challenge'][0]);
  }
  loggedin();
}

// Render login template
function login($error=false) {
  global $tpl;

  print $tpl->login(['error' => $error]);
  die;
}

// Login or challenge
function postLogin($username, $password) {
  global $ig;

  $_SESSION['username'] = $username;
  $_SESSION['password'] = $password;

  try {
    $ig->login($username, $password);
    unset($_SESSION['challenge']);

  // Wrong credentials
  } catch(\InstagramAPI\Exception\IncorrectPasswordException $e) {
    login(preg_replace('/^[^:]+: /', '', $e->getMessage()));

  // Requires challenge
  } catch(\InstagramAPI\Exception\ChallengeRequiredException $e) {
    $challengeData = $ig->getChallengeData($e);
    $challenge     = $ig->getChallenge($challengeData['url'], $challengeData['headers']);

    $_SESSION['challenge'] = $challengeData;
    formChallenge($challenge['entry_data']['Challenge'][0]);
  }
}

// Redirect to index
function loggedin() {
  unset($_SESSION['challenge']);

  $_SESSION = ['user' => [
    'username' => $_SESSION['username'],
    "password" => $_SESSION['password']
  ]];
  header('Location: ' . REWRITE_BASE);
  exit;
}

login();

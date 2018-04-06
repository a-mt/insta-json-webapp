<?php
if(isset($_SESSION['user'])) {
  header('Location: /');
  exit;
}

$ig = Instagram::withCredentials(@$_SESSION['username'], @$_SESSION['password']);

// POST username + password
if(isset($_POST['username'])) {

  try {
    post_login($_POST['username'], $_POST['password']);
  } catch(\InstagramScraper\Exception\InstagramAuthException $e) {
    login($e->getMessage());
  }
  loggedin();
}

// POST security code > login ?
if(isset($_POST['security_code'])) {
  try {
    $ig->post_securityCode($_POST['security_code'], $_POST['csrfmiddlewaretoken']);
  } catch(InstagramSecurityCode $e) {

    $response  = $e->get('response');
    $challenge = $ig->get_securityCodeForm($response);

    form_securityCode($challenge);
  }
  loggedin();
}

// POST challenge choice > enter security code
if(isset($_POST['choice'])) {
  if($challenge = $ig->post_challenge($_POST['choice'])) {
    form_securityCode($challenge);
  }
}

// Render login template
function login($error=false) {
  global $tpl;

  print $tpl->login(['error' => $error]);
  die;
}

// Login or challenge
function post_login($username, $password) {
  global $ig;
  $ig->setCredentials($username, $password);

  $_SESSION['username'] = $username;
  $_SESSION['password'] = $password;

  try {
    $ig->login(true);
    unset($_SESSION['challenge']);

  } catch(InstagramChallenge $e) {
    $response  = $e->get('response');
    $cookies   = $e->get('cookies');
    $challenge = $ig->get_challengeForm($response, $cookies);

    form_challenge($challenge['entry_data']['Challenge'][0]);
    die;
  }
}

// Render challenge template
function form_challenge($data) {
  global $tpl;
  $extra = [];

  foreach($data['extraData']['content'] as $extraData) {
    $name = $extraData['__typename'];
    unset($extraData['__typename']);
    $extra[$name] = $extraData;
  }
  $tpl->assign('challengeType', $data['challengeType']);
  $tpl->assign('extraData', $extra);
  $tpl->assign('fields', $data['fields']);

  print $tpl->challenge();
  die;
}

// Render challenge part 2 (type in the received security code)
function form_securityCode($data) {
  global $tpl;

  $tpl->assign('challengeType', 'Confirm');
  $tpl->assign('extraData', $data);
  $tpl->assign('fields', false);

  print $tpl->challenge();
  die;
}

// Redirect to index
function loggedin() {
  unset($_SESSION['challenge']);

  $_SESSION = ['user' => [
    'username' => $_SESSION['username']
  ]];
  header('Location: /');
  exit;
}

login();
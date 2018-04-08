<?php
use Unirest\Request;
\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

class InstagramChallengeException extends \InstagramAPI\Exception\InternalException {}

class HTMLParser extends DOMDocument {

  public function loadHTML($html, $options = NULL) {
    libxml_use_internal_errors(true);
    parent::loadHTML($html, $options);
    libxml_clear_errors();
  }
  public function getElementsByClassName($classname, $parentNode = null) {
    $xpath = new DomXPath($this);
    return $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]", $parentNode);
  }
}

class Instagram extends \InstagramAPI\Instagram
{
  /**
   * Request Instagram to get Challenge
   * @param string $url
   * @return array
   */
  function getChallenge($url, $headers) {
    $response = Request::get($url, $headers);
    $html     = $response->raw_body;

    // Retrieve value of _sharedData
    if (!preg_match('/window._sharedData\s\=\s(.*?)\;<\/script>/', $html, $matches)) {
        throw new InstagramChallengeException();
    }
    return json_decode($matches[1], true, 512, JSON_BIGINT_AS_STRING);
  }

  /**
   * Submit Challenge to Instagram and receive Security Code Form
   * @param string $choice
   */
  function postChallenge($url, $headers, $choice) {
    $response = Request::post($url, $headers, ['choice' => $choice]);
    $html     = $response->raw_body;

    if ($response->code !== 200) {
        throw new InstagramChallengeException();
    }
    if (!preg_match('/name="security_code"/', $html, $matches)) {
      throw new InstagramChallengeException();
    }
    return self::html_sharedData($html);
  }

  /**
   * Submit Security Code to Instagram and receive either confirmation or Security Code Form
   * @param string $securityCode
   */
  function postSecurityCode($username, $url, $headers, $securityCode) {
    $response = Request::post($url, $headers, ['security_code' => $securityCode]);

    if ($response->code !== 200) {
        throw new InstagramChallengeException();
    }
    $html = $response->raw_body;

    // We got back the security code form
    if (preg_match('/name="security_code"/', $html, $matches)) {
      return self::html_sharedData($response->raw_body);
    }

    // Save cookies jar
    $this->saveCookies($username, $response->headers['Set-Cookie']);
  }

  /**
   * Turns the HTML response into a list of fields to display
   * @param string $html
   * @return array
   */
  public static function html_sharedData($html) {

    // Return confirm form
    $data = [];
    $dom  = new HTMLParser;
    $dom->loadHTML($html);

    if(!$section = $dom->getElementsByTagName('section')) {
      throw new InstagramChallengeException();
    }

    // Get header, text
    $section = $section[0];
    if($item = $dom->getElementsByClassName("header", $section)) {
      $data['GraphChallengePageHeader'] = ['title' => $item[0]->nodeValue];
    }
    if($item = $dom->getElementsByClassName("gray-description-text", $section)) {
      $data['GraphChallengePageText'] = ['text' => $item[0]->nodeValue];
    }

    // Get fields
    $form = ['fields' => []];

    foreach($section->getElementsByTagName('form') as $_form) {
      if($_form->hasAttribute('id') && $_form->getAttribute('id') == "reset_progress_form") {
        continue;
      }
      foreach($_form->getElementsByTagName('input') as $_field) {
        $type = $_field->hasAttribute('type') ? $_field->getAttribute('type') : 'text';

        if($type == "submit") {
          $form['call_to_action'] = $_field->hasAttribute('value') ? $_field->getAttribute('value') : 'Submit';
        } else {
          $attrs = [];

          foreach ($_field->attributes as $attr) {
            $attrs[$attr->nodeName] = $attr->nodeValue;
          }
          $form['fields'][] = [
            'input_type' => $type,
            'values'     => [$attrs]
          ];
        }
      }
    }
    $data['GraphChallengePageForm'] = $form;
    $challenge = [
      'challengeType' => 'EnterSecurityCode',
      'fields'        => false,
      'extraData'     => $data
    ];

    return ['entry_data' => [
      'Challenge' => [$challenge]
    ]];
  }

  /**
   * @param string $username
   * @param array $cookieHeader
   */
  protected function saveCookies($username, $cookieHeader) {
    $account_id = "";

    // Create cookie jar
    $cookieJar = $this->getCookieJar($username);

    foreach($cookieHeader as $cookie) {
      $sc = \GuzzleHttp\Cookie\SetCookie::fromString($cookie);

      if(!$sc->getExpires()) {
        continue;
      }

      if($sc->getName() == "sessionid") {
        $sessionid  = urldecode($sc->getValue());
        $data       = json_decode('{' . explode('{', urldecode($sessionid), 2)[1], true);
        $account_id = $data['_auth_user_id'];
      }
      if(!$sc->getDomain()) {
        $sc->setDomain("i.instagram.com");
      }
      $cookieJar->setCookie($sc);
    }

    // Update client's cookieJar
    $prop = (new ReflectionObject($this->client))->getProperty('_cookieJar');
    $prop->setAccessible(true);
    $prop->setValue($this->client, $cookieJar);

    // Update loginState
    $this->isMaybeLoggedIn = true;
    $this->account_id = $account_id;

    $this->settings->set('account_id', $account_id);
    $this->settings->set('last_login', time());
  }

  /**
   * @param string $username
   * @return \GuzzleHttp\Cookie\CookieJar
   */
  protected function getCookieJar($username) {
    $this->settings->setActiveUser($_SESSION['username']);

    // Attempt to restore the cookies, otherwise create a new, empty jar.
    $cookieData      = $this->settings->getCookies();
    $restoredCookies = is_string($cookieData) ? @json_decode($cookieData, true) : [];

    if (!is_array($restoredCookies)) {
        $restoredCookies = [];
    }

    // Memory-based cookie jar which must be manually saved later.
    return new \GuzzleHttp\Cookie\CookieJar(false, $restoredCookies);
  }
}
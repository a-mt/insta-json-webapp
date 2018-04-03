<?php
use InstagramScraper\Exception\InstagramAuthException;
use InstagramScraper\Exception\InstagramException;
use InstagramScraper\Exception\InstagramNotFoundException;
use InstagramScraper\Model\Account;
use InstagramScraper\Model\Comment;
use InstagramScraper\Model\Like;
use InstagramScraper\Model\Location;
use InstagramScraper\Model\Media;
use InstagramScraper\Model\Story;
use InstagramScraper\Model\Tag;
use InstagramScraper\Model\UserStories;
use InstagramScraper\Endpoints;
use phpFastCache\CacheManager;
use Unirest\Request;

class InstagramChallenge extends Exception {
    protected $_extra;

    public function __construct($message="", $code=0, Exception $previous=NULL, $extra = []) {
        $this->_extra = $extra;
        parent::__construct($message, $code, $previous);
    }
    public function get($field) {
        return isset($this->_extra[$field]) ? $this->_extra[$field] : null;
    }
}
class InstagramSecurityCode extends InstagramChallenge {}

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

class Instagram extends \InstagramScraper\Instagram
{
    /**
     * Sets the username and password
     * 
     * @param string $username
     * @param string $password
     */
    public function setCredentials($username, $password) {
        $this->sessionUsername = $username;
        $this->sessionPassword = $password;
    }

    /**
     * Try to login with the credentials we got
     * @throws InstagramAuthException | InstagramChallenge
     * @param bool[optional] $force - [false]
     */
    public function login($force = false, $support_two_step_verification = false) {
        return parent::login($force, true);
    }

    /**
     * Login: Instagram responds a challenge (mail, phone, simple click...)
     * @param \Unirest\Response $response
     * @param array $cookies
     * @throws InstagramChallenge
     */
    protected function verifyTwoStep($response, $cookies) {
        throw new InstagramChallenge($response->body->message, 0, null, [
          "response" => $response,
          "cookies"  => $cookies
        ]);
    }

    /**
     * Get the challenge form
     * 
     * @param \Unirest\Response $response
     * @param array $cookies
     * @return array
     */
    public function get_challengeForm($response, $cookies) {

        // Retrieve challenge
        $new_cookies   = static::parseCookies($response->headers['Set-Cookie']);
        $cookies       = array_merge($cookies, $new_cookies);
        $cookie_string = '';

        foreach ($cookies as $name => $value) {
            $cookie_string .= $name . "=" . $value . "; ";
        }
        $headers = [
          'cookie'      => $cookie_string,
          'referer'     => Endpoints::LOGIN_URL,
          'x-csrftoken' => $cookies['csrftoken']
        ];

        $url      = Endpoints::BASE_URL . $response->body->checkpoint_url;
        $response = Request::get($url, $headers);

        // Retrieve value of _sharedData
        if (!preg_match('/window._sharedData\s\=\s(.*?)\;<\/script>/', $response->raw_body, $matches)) {
            throw new InstagramAuthException('Something went wrong when try challenge. Please report issue.');
        }
        $data = json_decode($matches[1], true, 512, JSON_BIGINT_AS_STRING);

        $_SESSION['challenge'] = [
            'url'     => $url,
            'headers' => $headers
        ];
        return $data;
    }

    /**
     * Submit the challenge (send mail)
     * 
     * @throws InstagramAuthException | InstagramSecurityCode
     * @param string $choice
     */
    public function post_challenge($choice) {
        if(!$_SESSION['challenge']) {
            return;
        }

        $url      = $_SESSION['challenge']['url'];
        $headers  = $_SESSION['challenge']['headers'];
        $response = Request::post($url, $headers, ['choice' => $choice]);

        if (!preg_match('/name="security_code"/', $response->raw_body, $matches)) {
            throw new InstagramAuthException('Something went wrong when try challenge. Please report issue.');
        }
        return self::get_securityCodeForm($response);
    }

    /**
     * Turns the HTML response into a list of fields to display
     * @param \Unirest\Response $response
     * @return array
     */
    public static function get_securityCodeForm($response) {

        // Return confirm form
        $data = [];
        $dom  = new HTMLParser;
        $dom->loadHTML($response->raw_body);

        if(!$section = $dom->getElementsByTagName('section')) {
            throw new InstagramAuthException('Something went wrong when try challenge. Please report issue.');
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
        return $data;
    }

    /**
     * Submit the security code
     * 
     * @throws InstagramAuthException | InstagramSecurityCode
     * @param string $security_code
     * @param string $csrftoken
     * @return array
     */
    public function post_securityCode($security_code, $csrftoken) {
        $url      = $_SESSION['challenge']['url'];
        $headers  = $_SESSION['challenge']['headers'];

        $post_data = [
            'csrfmiddlewaretoken' => $csrftoken,
            'verify'              => 'Verify Account',
            'security_code'       => $security_code,
        ];
        $response = Request::post($url, $headers, $post_data);
        if ($response->code !== 200) {
            throw new InstagramAuthException('Something went wrong when try enter security code. Please report issue.');
        }

        // We got back the security code form
        if (preg_match('/name="security_code"/', $response->raw_body, $matches)) {
            throw new InstagramSecurityCode('securitycode_required', 0, null, [
                'response' => $response
            ]);
        }

        // Looks like we've logged in: retrieve cookies
        $cookies = static::parseCookies($response->headers['Set-Cookie']);
        $this->userSession = $cookies;

        // Cache them
        $cachedString = static::$instanceCache->getItem($this->sessionUsername);
        $cachedString->set($cookies);
        static::$instanceCache->save($cachedString);

        return $this->generateHeaders($this->userSession);
    }
}
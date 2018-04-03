<?php
class TemplateException extends Exception {}

class Template {
  protected static $instance;
  protected $args = [];

  public function __construct() {
    $this->dir = __DIR__ . '/../tpl/';
  }

  /**
   * Set an argument that will get passed to the template
   * @param string $name
   * @param mixed $value
   */
  public function assign($name, $value) {
    $this->args[$name] = $value;
  }

  /**
   * Returns the template's content
   * @throw TemplateException - Template not found
   * @return string
   */
  public function __call($name, $arguments) {

    if(isset($arguments[0]) && is_array($arguments[0])) {
      $assign = $arguments[0];
    } else {
      $assign = [];
    }

    // Get template's content
    $content    = $this->render($name, $assign);
    $this->args = [];

    // Encapsulate with layout
    return $this->render('_layout', ["content" => $content]);
  }

  /**
   * @param string $name
   * @param array[optional] $assign - []
   * @return string
   */
  public function render($name, $assign=[]) {
    if(!file_exists($this->dir . 'tpl.' . $name . '.php')) {
      throw new TemplateException($name . ' template doesn\'t existing');
    }

    // Assign arguments
    extract($this->args);
    extract($assign);

    // Get template's content
    ob_start();
    include $this->dir . 'tpl.' . $name . '.php';
    return ob_get_clean();
  }
}

$tpl = new Template();
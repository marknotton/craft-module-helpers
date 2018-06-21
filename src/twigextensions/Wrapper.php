<?php

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;
use craft\helpers\ElementHelper;

use Craft;

class Wrapper extends \Twig_Extension {

  private $tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'ol', 'ul', 'li', 'div', 'pre', 'section', 'footer'];
  private $singletons = ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta','param', 'source'];

  public function getFilters() {

    $filters = [
      new \Twig_SimpleFilter('wrap'  , [$this, 'wrapper'],   ['is_safe' => ['html']] ),
      new \Twig_SimpleFilter('unwrap', [$this, 'unwrapper'], ['is_safe' => ['html']] )
    ];

    foreach ($this->tags as &$tag) {
      $filters[] = new \Twig_SimpleFilter($tag,
        [$this, $tag], ['is_safe' => ['html']]
      );
    };

    return $filters;

  }

  /**
  * Captures any methods that share the same name as one of the $tags
  *
  * @return array
  */
  public function __call($method, $params) {

    if (in_array($method, $this->tags)){

      // Put the method into the second position
      array_splice($params, 1, 0, $method);

      return $this->wrapper(true, $params);
    }
  }

  /**
   *
   * Wrap content with html tags
   *
   * @param string $html  The content that is to be wrapped
   * @param string $element  The tag name/s
   * @param string $class  Add a class to the most parent tag
   * @param array $data Add data attributes to the most parent tag
   * @return string
   */
  public function wrapper() {

    $arguments = [];

    // Atleast one symbol sting arugment should be passed
    if ( func_num_args() < 1 ){
      return false;
    }

    // Dynamic filter functions pass in wrap arguments into an array.
    // This is distinguish this and set the arguments as if it were called normmally.
    if ( func_get_args()[0] === true) {
      $arguments = func_get_args()[1];
    } else {
      $arguments = func_get_args();
    }

    // The first argument is the content that is automatically passed.
    $html = $arguments[0];

    // Return false is no MTML is passed
    if ( empty($html) ){
      return false;
    }

    // Remove the first argument and set the argumetns array
    $arguments = array_slice($arguments, 1);

    // Default settings
    $elements = null;
    $class    = null;
    $data     = null;

    // Private
    $_first    = false;
    $_openers  = [];
    $_closers  = [];

    if ( !empty($arguments) ) {

      // Loop through arguments and define settings
      foreach ($arguments as &$setting) {

        // Element and class
        if (is_string($setting)) {
          if ( is_null($elements) ) {
            $elements = $setting;
          } else if ( is_null($class) ) {
            $class = $setting;
          }
        }

        // Data
        if (is_array($setting)) {
          if ( is_null($data) && count($setting) == 2 ) {
            $data = $setting;
          }
        }

      }
    }

    if (isset($elements)) {

      $elementsArray = explode(' ', $elements);

      foreach ($elementsArray as &$element ) {
        $singleton = false;

        if (in_array($element, $this->singletons)) {
          $singleton = true;
          switch ($element) {
            case "base":
              $output = "<base href='".$html."'>";
            break;
            case "img":
              $output = "<img src='".$html."' alt='".$html."'>";
            break;
            case "embed":
              $output = "<embed src='".$html."'>";
            break;
            case "link":
              $output = "<link href='".$html."'>";
            break;
            case "source":
              $output = "<source src='".$html."'>";
            break;
          }

          array_push($_openers, $output);

        } else {

          if (!$_first) {
            $_first = true;

            $firstElement = '<'.$element;

            // If selector starts with a hash, define an ID instead of a class
            if ( isset($class) && $class != '') {
              $firstElement .= ($class[0] == '#') ? ' id="'.str_replace("#", "", $class).'"' : ' class="'.$class.'"';
            }

            // If array is passed with two elements, assume the first is a data-attribute name, and the second is the data attribute value
            if ( isset($data) ) {
              $firstElement .= ' data-'.$data[0].'="'.$data[1].'"';
            }

            array_push($_openers, $firstElement.'>');

          } else {

            array_push($_openers, '<'.$element.'>');

          }

          if (!$singleton) {
            array_push($_closers, '</'.$element.'>');
          }
        }
      }

      return implode("",$_openers).(!$singleton ? rtrim($html) : null).implode("", array_reverse($_closers));

    } else {
      return $html;
    }
  }

  /**
   *
   * Unwrap content by specific html tags
   *
   * @param string $html  The content that is to be unwrapped
   * @param string $allow  Allow specific tags you want to be kept.
   * @return string
   *
   * @example {{ "<h1><span><cite> title </cite></span></h1>"|unwrap('h1') }}
   */
  public function unwrapper($html, $allow=null) {
    if (!is_null($allow) && is_string($html)) {
      // Remove any '<' and '>' if they exists
      $allow = str_replace(array('<', '>'), ' ', $allow);
      // Clear any empty elements and add everything to the allow array, so they should just be words/letters
      $allow = array_filter(explode(' ', $allow));
      // Now we have a clean array, regardless of how the allow conditions were passed.
      // Lets reapply the '<' and '>' bits to each element.
      array_walk($allow, function(&$item) { $item = '<'.$item.'>'; }); // or ;
      // And convert it all back to a string
      $allow = '"'.implode('', $allow).'"';
    }

    return strip_tags($html, $allow).html_entity_decode('');
  }

}

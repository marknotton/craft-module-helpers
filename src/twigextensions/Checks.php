<?php

namespace modules\helpers\twigextensions;

use Craft;
use modules\helpers\Helpers;

class Checks extends \Twig_Extension {

  private $checks   = ['string', 'number', 'blank', 'array', 'object', 'url'];
  private $elements = ['asset', 'category', 'entry', 'globalset', 'matrixblock', 'tag', 'user', 'single'];

  /**
  * Generates all the test methods dynamically
  *
  * @return array
  */
  public function getTests() {
    $tests = [];

    foreach (array_merge($this->checks, $this->elements) as &$check) {
      $tests[] = new \Twig_SimpleTest($check, [$this, $check]);
    };

    return $tests;

  }

  /**
  * Generates all filter methods
  *
  * @return array
  */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('fileexists', [Helpers::$app->request, 'fileexists']),
    ];
  }

  /**
  * Is String
  *
  * @return bool
  */
  public function string($value) {
    return is_string($value);
  }

  /**
  * Is Number
  *
  * @return bool
  */
  public function number($value) {
    return is_numeric($value) && (intval($value) == $value || floatval($value) == $value);
  }

  /**
  * Is blank - is defined and is not empty
  *
  * @return bool
  */
  public function blank($value) {
    return empty($value) || strlen(trim($value)) === 0;
  }

  /**
  * Is Array
  *
  * @return bool
  */
  public function array($value) {
    return is_array($value);
  }

  /**
  * Is Object
  *
  * @return bool
  */
  public function object($value) {
    return is_object($value);
  }

  /**
  * Is URL
  *
  * @return bool
  */
  public function url($value) {
    return filter_var($value, FILTER_VALIDATE_URL);
  }

  /**
  * Captures any methods that share the same name as one of the items in the $elements array
  *
  * @return array
  */
  public function __call($type, $element) {

    if (in_array($type, $this->elements)){
      return Helpers::$app->services->getElementType($element[0], $type);
    }
  }

  // TODO: These are not functioning

  // Is Single
  public function single($element) {
    return $element->getSection()->type == 'single' ?? $element[0]->getSection()->type == 'single' ?? false;
  }

  // Is Channel
  public function channel($element) {
    return $element->getSection()->type == 'channel' ?? $element[0]->getSection()->type == 'channel' ?? false;
  }

}

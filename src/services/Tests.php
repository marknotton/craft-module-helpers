<?php

namespace modules\helpers\services;

use modules\helpers\Helpers;

use Craft;
use craft\base\Component;

class Tests extends Component {

  public function init() {
    $this->addTests(Helpers::$instance->twig);
    // $this->addTests(Craft::$app->view->getTwig());
  }

  public function addTests($twig) {

    // Is String
    $twig->addTest(new \Twig_SimpleTest('string', function ($value) {
      return is_string($value);
    }));

    // Is Number
    $twig->addTest(new \Twig_SimpleTest('number', function ($value) {
      return is_numeric($value) && (intval($value) == $value || floatval($value) == $value);
    }));

    // Is blank - is defined and is not empty
    $twig->addTest(new \Twig_SimpleTest('blank', function ($value) {
      return empty($value) || strlen(trim($value)) === 0;
    }));

    // Is Array
    $twig->addTest(new \Twig_SimpleTest('array', function ($value) {
      return is_array($value);
    }));

    // Is Object
    $twig->addTest(new \Twig_SimpleTest('object', function ($value) {
      return is_object($value);
    }));

    // TODO: Update these to work with Craft 3.
    // Is Single
    // $twig->addTest(new \Twig_SimpleTest('single', function ($element) {
    //   return $element->getSection()->type == 'single' || $element[0]->getSection()->type == 'single';
    // }));

    // TODO: Update these to work with Craft 3.
    // Is Channel
    // $twig->addTest(new \Twig_SimpleTest('channel', function ($element) {
    //   return $element->getSection()->type == 'channel' || $element[0]->getSection()->type == 'channel';
    // }));

    // TODO: Update these to work with Craft 3.
    // Is Entry
    // $twig->addTest(new \Twig_SimpleTest('entry', function ($element) {
    //   return $element[0]->getElementType() == ElementType::Entry || $element->getElementType() == ElementType::Entry;
    // }));

    // TODO: Update these to work with Craft 3.
    // Is Category
    // $twig->addTest(new \Twig_SimpleTest('category', function ($element) {
    //   return $element[0]->getElementType() == ElementType::Category || $element->getElementType() == ElementType::Category;
    // }));

    // TODO: Update these to work with Craft 3.
    // Is Tag
    // $twig->addTest(new \Twig_SimpleTest('tag', function ($element) {
    //   return $element[0]->getElementType() == ElementType::Tag || $element->getElementType() == ElementType::Tag;
    // }));

  }

}

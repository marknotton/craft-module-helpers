<?php

/**
 * Methods for activly doing something
 */

namespace modules\helpers\services;
use modules\helpers\Helpers;

use Craft;
use craft\web\View;
use craft\base\Component;
use craft\helpers\StringHelper;
use craft\helpers\Template;

class Services extends Component {

  public $session;

  public function init() {
    $this->session = Craft::$app->getSession();
  }

  //////////////////////////////////////////////////////////////////////////////
  // Clean up class names
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Run through a string of classes and sanitise them
   * Remove numbers from the start. Lower case.
   *
   * @return string
   */
  public function sanitiseClasses($classes) {

    if (gettype($classes) == 'string') {
      // turn string to an array
      $classes = explode(" ", $classes);
    }

    $sanitizedClasses = [];

    // Loop through each string and remove any numbers from the start of one.
    // Then kebabCase each one.
    foreach ($classes as $class) {
      $class = preg_replace('#^\d+#', '', $class);
      $sanitizedClasses[] = StringHelper::toKebabCase($class);
    }

    // Remove duplicate classes
    $sanitizedClasses = array_unique($sanitizedClasses);

    // Convert back to space delimted string
    $sanitizedClasses = implode(" ", $sanitizedClasses);

    // Remove duplciate white spaces
    $sanitizedClasses = preg_replace('/\s+/', ' ', $sanitizedClasses);

    // Remove whitespace from ends
    $sanitizedClasses = trim($sanitizedClasses);

    return !empty($sanitizedClasses) ? $sanitizedClasses : false;

  }

  public function cleanup($data) {

    if(is_object($data)) {
      $data = $data->getParsedContent();
    }

    $regex = '~<((?!iframe|canvas|use)\w+)[^>]*>(?>[\p{Z}\p{C}]|<br\b[^>]*>|&(?:(?:nb|thin|zwnb|e[nm])sp|zwnj|#xfeff|#xa0|#160|#65279);|(?R))*</\1>~ui';

    $clensed = preg_replace($regex, '', $data);

    $clensed = preg_replace('~(<br */?>\s*)+~ui', '$1', $clensed);

    return Template::raw($clensed);

  }

  //////////////////////////////////////////////////////////////////////////////
  // Sessions
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Set a session name
   *
   * @param string
   */
  public function setSession($key = null, $value = true) {
    $this->session->set($key, $value);
  }

  /**
   * Get a session name
   *
   * @param string
   * @return string
   */
  public function getSession($key = null) {
    return $this->session->get($key);
  }

  /**
   * Returns a relative URL
   *
   * @return string
   */
  public function relativeUrl($path) {
    if ( filter_var($path, FILTER_VALIDATE_URL) ) {
      return parse_url($path, PHP_URL_PATH);
    }
    return $path;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Check element type
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Check the element type of a given elemt
   * @param  [object] $element - Pass the actual element
   * @param  [string] $type - Pass the entry you want to check to return a bool.
   *                          Leave blank if you want to return a string of the entry type
   * @return [bool | string]
   */
  public function getElementType($element, $type = null) {
    $elementType  = Craft::$app->getElements()->getElementTypeById($element->id);
    $elementType  = explode('\\',$elementType);
    $elementType  = end($elementType);
    $elementType  = strtolower($elementType);

    if (!empty($type)) {
      // Perform a check and return a bool
      return $elementType == $type;
    } else {
      // Return the element type as a string
      return $elementType;
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Helpful functions
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Same as array_merge_recursive, only this overrights an existing keys
   * @see http://php.net/manual/en/function.array-merge-recursive.php
   * @return array
   */
  public function deepMerge(array &$array1, array &$array2) {
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = $this->deepMerge($merged[$key], $value);
      } else {
        $merged[$key] = $value;
      }
    }
    return $merged;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Themer
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Theme the CMS to match the current sites defined colour options.
   * @see config/settings.php to define a primary and secodonary theme colour.
   */
  public function themer() {
    // Add a CSS custom variable if theme colour is found in the config/settings.php
    if ( Helpers::$settings['cms']['themed'] ?? false ) {
      if ($themes = Helpers::$settings['themes'] ?? false) {
        $themes = is_array($themes) ? array_values($themes) : [$themes];
        Craft::$app->getView()->registerCss('html { --primary:'.($themes[0]).'; --secondary:'.($themes[1] ?? $themes[0]).' }');
      } elseif ($theme = Helpers::$settings['theme'] ?? false) {
        // Add a CSS custom variable if theme colour is found in the config/settings.php
        Craft::$app->getView()->registerCss('html { --primary:'.$theme.' }');
      }
      // If this is a multisite, include a settings file bespoke to the current site.
      $sites = Craft::$app->getSites();

      if ( count($sites->getAllSites()) > 1 ) {
        $current = $sites->getCurrentSite();
        Craft::$app->getView()->registerJs('var site = {name:"'.$current->name.'",handle:"'.$current->handle.'"}', \yii\web\View::POS_HEAD);
      }
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Installer
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Pre-populate input fields during the innitial instlalation process
   */
  public function installation() {
    if ( !Craft::$app->getIsInstalled() ) {
      Craft::$app->view->registerJsVar ('email', Helpers::$general->testToEmailAddress ?? 'technical@yello.studio');
      Craft::$app->view->registerJsVar ('project', Helpers::$config['project'] ?? null);
      Craft::$app->view->registerJsFile(
        Craft::$app->getAssetManager()->getPublishedUrl('@helpers/assets/scripts/', true, 'installer.js'),
        ['position' => constant('\\yii\\web\\View::POS_END')]
      );
    }
  }

}

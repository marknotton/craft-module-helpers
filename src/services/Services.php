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

    $regex = '~<((?!iframe|canvas|use|textarea|featured-image|select)\w+)[^>]*>(?>[\p{Z}\p{C}]|<br\b[^>]*>|&(?:(?:nb|thin|zwnb|e[nm])sp|zwnj|#xfeff|#xa0|#160|#65279);|(?R))*</\1>~ui';

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
      if ($themes = Helpers::$settings['cms']['theme'] ?? Helpers::$settings['themes'] ?? false) {
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

  /**
   * Format a data size into a legible string
   * @param  [number] $size [description]
   * @return [string]
   */
  public function formatSize($size) {
      $sizes = ['B','KB','MB','GB','TB','PB'];
      $key=0;
      while($size > 1024) {
          $size /= 1024;
          $key++;
      }
      return number_format($size,2).$sizes[$key];
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

  //////////////////////////////////////////////////////////////////////////////
  // Parameters
  //////////////////////////////////////////////////////////////////////////////

/**
 * Addor update paramers withing a string that is a URL
 * @return string [description]
 * @see https://github.com/marknotton/craft-plugin-youarel#params
 * @param  [string] $url         Pass in a URL you want to modify, or omit this and the current page URL will be used instead.
 * @param  [array] $parameters   Add an associative array to add multiple values to the url.
 * @return [string]              URL
 */

  function params(){

    // Fail if no parameters are passed
    if ( func_num_args() < 1 ){
      return false;
    }

    $arguments = func_get_args();

    $argumentsCount = func_num_args();

    $url      = null;
    $variable = null;
    $value    = null;
    $newParams= null;

    if ( isset($arguments) ){
      foreach ($arguments as &$setting) {
        if ( gettype($setting) == 'array') {
          $newParams = $setting;
        } else if (filter_var($setting, FILTER_VALIDATE_URL) || strpos($setting, '.') || strpos($setting, '/')) {
          $url = $setting;
        } else if ( is_null($variable)) {
          $variable = $setting;
        } else if ( is_null($value)) {
          $value = $setting;
        }
      }
    }

    // If an array of settings was passed, ignore any strings that might have been passed
    if (!is_null($newParams) ) {
      $variable = null;
      $value    = null;
    }

    // If both a variable and value were set, create a new array
    if (!is_null($variable) && !is_null($value)) {
      $newParams = array($variable => $value);
    }

    // If a variable was set, but not a value... assume this was meant to be the opposite. So a value can be add/updated even without the variable name
    if (!is_null($variable) && is_null($value)) {
      $newParams = array(false => $variable);
    }

    // Use current url if one was not defined
    if (is_null($url)) {
      $url = (isset($_SERVER['HTTPS']) ? "https" : "http")."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    // When no value or an array were passed, just return the original url
    if (is_null($newParams) && is_null($value)) {
      return $url;
    }

    // Returns any existing parameters from the $url, as a string
    $currentParams = parse_url($url, PHP_URL_QUERY);

    // Update parameters if they exist
    if (!is_null($currentParams)) {
      foreach(explode("&", $currentParams) as $query) {

        // Asign $key as the variable, and $value as the value. If a variable isn't defined, set it to null
        list($key, $value) = (strpos($query, '=') !== false) ? explode("=", $query) : [null, $query];

        if(array_key_exists($key, $newParams)) {
          if($newParams[$key]) {
            // Updates first instance of an existing parameter
            $url = preg_replace('/'.$key.'='.$value.'/', $key.'='.$newParams[$key], $url);
          } else {
            // Removes any duplicates
            $url = preg_replace('/&?'.$key.'='.$value.'/', '', $url);
          }
        }
      }
    }

    // Add any new parameters
    foreach($newParams as $key => $value) {
      if($value && !preg_match('/'.$key.'=/', $url)) {
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?').($key != false ? $key.'=' : '').$value;
      }
    }

    return $url;
  }
}

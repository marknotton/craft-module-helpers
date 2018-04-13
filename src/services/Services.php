<?php

namespace modules\helpers\services;

use modules\helpers\Helpers;

use Craft;
use craft\web\View;
use craft\base\Component;
use craft\helpers\StringHelper;


class Services extends Component {

  /**
   * Combine site settings files
   *
   * @return array
   */
  public function init() {
    Helpers::$instance->settings = $this->getSettings();
  }

  public function getSettings() {
    // Make all keys/values in the config/settings.php available in Twig
    $settings = Craft::$app->getConfig()->getConfigFromFile('settings');

    // If this is a multisite, include a settings file bespoke to the current site.
    $sites = Craft::$app->getSites();

    if ( count($sites->getAllSites()) > 1 ) {
      $current = $sites->getCurrentSite();
      $siteSettings = Craft::$app->getConfig()->getConfigFromFile($current->handle);
      $settings = $this->array_merge_recursive_distinct( $settings, $siteSettings );
    }

    return $settings;

  }

  /**
   * Run through a string of classes and sanitise them
   * Remove numbers from the start. Lower case.
   *
   * @return string
   */
  public function sanitiseClasses(string $classes) {
    $classes = explode(" ", $classes);

    $sanitizedClasses = [];

    foreach ($classes as $class) {
      $class = preg_replace('#^\d+#', '', $class);
      $sanitizedClasses[] = StringHelper::toKebabCase($class);
    }

    return trim(implode(' ', array_unique($sanitizedClasses)));

  }

  public function relativeUrl($path) {
    return parse_url($path, PHP_URL_PATH);;
  }

  /**
   * List all plugins and their status into an array
   *
   * @return array
   */
  public function enabledPlugins() {

    $newResults = [];

    if ( Helpers::$instance->databaseConnected ) {

      $sql = "SELECT handle, enabled FROM ".getenv('DB_TABLE_PREFIX')."plugins" ;

      $command = Craft::$app->db->createCommand($sql);
      $results = $command->queryAll();
      if ($results) {
        foreach ($results as $value) {
          $newResults[$value['handle'].'Enabled'] = boolval($value['enabled']);
        }
      }
    }

    return $newResults;
  }

  /**
   * Same as array_merge_recursive, only this overrights an existing keys
   * @see http://php.net/manual/en/function.array-merge-recursive.php
   * @return array
   */
  public function array_merge_recursive_distinct(array &$array1, array &$array2) {
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }
    return $merged;
  }

}

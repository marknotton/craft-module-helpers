<?php

/**
 * Methods requesting and checking data
 */

namespace modules\helpers\services;
use modules\helpers\Helpers;

use Craft;
use craft\base\Component;
use craft\helpers\StringHelper;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use craft\web\twig\variables\Rebrand;
use craft\helpers\Template;

class Requests extends Component {

  //////////////////////////////////////////////////////////////////////////////
  // Error Information
  //////////////////////////////////////////////////////////////////////////////

  public $errors = [
    400 => [
      'type'    => "Bad Request",
      'message' => "The request could not be understood by the server due to malformed syntax."
    ],
    403 => [
      'type'    => "Forbidden",
      'message' => "You don’t have the proper credentials to access this page."
    ],
    404 => [
      'type'    => "Page Not Found",
      'message' => "This page could not be found."
    ],
    500 => [
      'type'    => "Internal server error",
      'message' => "The server encountered an unexpected condition which prevented it from fulfilling the request."
    ],
    503 => [
      'type'    => "Holding Page",
      'message' => "is currently undergoing maintenance."
    ]
  ];

  //////////////////////////////////////////////////////////////////////////////
  // Settings
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Return content from the config/settings.php
   * Also check for other config files that share the sanme name as the
   * current site handle. If one is found (e.g. ducati.php), then deepmerge
   * all the settings. Site specific settings will overwrite the default settings where present.
   *
   * @return array
   */
  public function getSettings($includeConfigJson = true) {
    // Make all keys/values in the config/settings.php available in Twig
    $settings = Craft::$app->getConfig()->getConfigFromFile('settings') ?? false;
    $helpers  = Craft::$app->getConfig()->getConfigFromFile('helpers') ?? false;

    // If theme isn't defined, but a themes array is.
    // Use the first themes elements and set them as a theme.
    if ( !isset($settings['theme']) && isset($settings['themes']) && is_array($settings['themes']) ) {
      $settings['theme'] = array_values($settings['themes'])[0];
    }

    if ( $helpers ) {
      $settings = array_merge($helpers, $settings);
    }

    if ( $includeConfigJson ) {
      $settings = array_merge($this->getConfig(), $settings);
    }

    // If this is a multisite, include a settings file bespoke to the current site.
    $sites = Craft::$app->getSites();

    if ( count($sites->getAllSites()) > 1 ) {
      $current = $sites->getCurrentSite();
      $siteSettings = Craft::$app->getConfig()->getConfigFromFile($current->handle);
      $settings = Helpers::$app->service->deepMerge( $settings, $siteSettings );
    }

    return $settings;
  }

  /**
   * Get specific data from the config.json file. Also do a few environmental
   * checks to filter down the results.
   * @param  [type] $env [description]
   * @return [type]      [description]
   */
  public function getConfig($env = null) {

    if ($cache = Helpers::$config) {
      return $cache;
    }

    $settings = [];
    $env = $env ?? getenv('ENVIRONMENT');

    try {
      $config = json_decode(file_get_contents(Craft::getAlias('@root').'/config.lock'), true);

      if ( is_null($config)) {
        throw new \Exception('config.json file invalid');
      }

      // These are the settings that will be taken from the config.json file
      // and made globablly available in Twig.
      $get = ['host', 'settings', 'paths', 'filenames', 'project', 'themes', 'organisation'];

      // These objects will not be available by their key, as all first level
      // children will be passed into the root.
      $dontNest = ['settings', 'paths'];

      foreach ($get as &$setting) {

        $flat = in_array($setting, $dontNest);

        if ( $configSettings = $config[$setting] ?? false ) {

          if (is_string($config[$setting]) || is_int($config[$setting])) {

            $settings[$setting] = $config[$setting];

          } else {
            $keys = array_keys($configSettings);

            if ( $flat ) {
              $settings = array_merge($settings, $configSettings);
            } else {
              $settings[$setting] = $configSettings;
            }
          }
        }
      }

    } catch (\Exception $e) {
      throw new \yii\base\ErrorException($e->getMessage());
    }

    Helpers::$config = $settings;

    return $settings;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Current Element
  ///////////////////////////////////////////////////////////////////////////////

  /**
   * Get the current element type. Designed primarily to get the current entry.
   * @param  string $uri    Pass in a specific tempalte URI. Leave blank for current entry
   * @param  int    $siteId Define the Site ID you want the element ID to be for.
   * @return object         Return element object
   */
  public function getCurrentElement(string $uri = null, int $siteId = null) {

    if ($cache = Helpers::$element) {
      return $cache;
    }

    if ( Helpers::$app->query->isDatabaseConnected() ?? false ) {

      if ($siteId === null ) {
        $sites = Craft::$app->getSites();
        $siteId = $sites->currentSite->id ?? $sites->primarySite->id ?? 1;
      }

      if ($uri === null) {
        $uri = Craft::$app->getRequest()->getPathInfo() ?? $_SERVER['REQUEST_URI'];
      }

      $uri = trim($uri,'/');

      $element = Craft::$app->getElements()->getElementByUri($uri, $siteId, false);

      return $element;

    }

  }

  //////////////////////////////////////////////////////////////////////////////
  // File Exists
  //////////////////////////////////////////////////////////////////////////////

  /**
   * When check for a files existance, just use this fill. If it doesn't exist, false is return. Otherwise return the url
   * @param  string $file url string
   * @param  string $fallback If the file doesn't exist. The fallback will be returned. Fallback files are not checked for their existence
   * @example Helpers::$app->request->fileexists(...);
   * @return bool
   */
  public function fileexists(string $file, string $fallback = null) {

    if (gettype($file) == 'string') {
      // Remove the first slash if it exists
      $file = trim($file, '/');

      // Remove any paramters in the URL for the check.
      // TODO: Put the params back on the url for the ruen value;
      $file = strtok($file, '?');

      // http://stackoverflow.com/a/2762083/843131
      if (preg_match("~^(?:f|ht)tps?://~i", $file)) {
        // Absolute URL
        return file_exists($file) ? $file : (!empty($fallback) ? $fallback : false);
      } else {
        // Relative URL
        return file_exists(getcwd().'/'.$file) ? '/'.ltrim($file, '/') : (!empty($fallback) ? $fallback : false);
      }
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // File Directory
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Recursively scan all files and direcotries in a given path. Return all
   * the folder and file names in a nest array. Whilst excluding a few gremlins.
   * @param  string $dir directory name/path. Defaults to the templates directory.
   * @param  bool $nested `true` : nest file structure into arrays.
   *                      `false` : list all paths as array of strings [default]
   * @example Helpers::$app->request->getFileDirectory();
   * @return array
   */
  public function getFileDirectory(string $dir = null, $nested = false) {
    $dir = $dir ?? Craft::getAlias('@templates');
    $result = [];

    if ($nested) {
      $cdir = scandir($dir);
      foreach ($cdir as $key => $value) {
        if (!in_array($value,array(".","..")) && substr($value, 0, 1) !== '.')  {
          if (is_dir($dir . DIRECTORY_SEPARATOR . $value)){
            $result[$value] = $this->getFileDirectory($dir . DIRECTORY_SEPARATOR . $value, $nested);
          } else {
            $result[] = $value;
          }
        }
      }
    } else {

      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST);

      foreach ($iterator as $file) {
        if ($file->isDir() || substr($file->getFilename(), 0, 1) === '.' ){ continue; }
        $result[] = ltrim(str_replace($dir, '', $file->getPathname()), '/');
      }
    }

    return $result;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Module Asset
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Get module files releative from the modules/helpers/assets directory
   *
   *  @param string $file path and filename
   *  @param bool $relative If true, will return the filename as a relative URI. Defaults to true
   *
   * @example PHP: Helpers::$app->query->asset('scripts/template-fetcher.es5.js')
   * @example Twig: {{ craft.helpers.asset('scripts/template-fetcher.es5.js' )}}
   * This will grab a file from, `modules/helpers/src/assets/scripts/template-fetcher.es5.js`
   * This will refer to a cached version of the file that lives in the public directory: public/assets/resources/*
   * @return string
   */
  public function asset($file, $relative = true)  {
    $url = \Craft::$app->assetManager->getPublishedUrl("@helpers/assets/", true, $file);
    if ($relative === true) {
      $url = Helpers::$app->service->relativeUrl($url);
    }
    return $url;
  }


  //////////////////////////////////////////////////////////////////////////////
  // Rebrand
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Get the sites brand assets (icon and logo)
   *
   * @return array
   */

  public $rebrand;

  public function rebrand($relative = true)  {

    $rebrand = false;

    if ( Craft::$app->getEdition() != '0' ) {

      if ( $cached = $this->rebrand ) {
        $cached = new Rebrand();
      }


      if ($this->rebrand) {

        $logo = strtok($this->rebrand->getLogo()->getUrl(), '?');
        $icon = strtok($this->rebrand->getIcon()->getUrl(), '?');

        if ($relative === true) {
          $logo = Helpers::$app->service->relativeUrl($logo);
          $icon = Helpers::$app->service->relativeUrl($icon);
        }

        $rebrand = ['logo' => $logo, 'icon' => $icon];

      }

    }

    return $rebrand;
  }


  //////////////////////////////////////////////////////////////////////////////
  // Load Scripts as per the config.json & settings.php options
  //////////////////////////////////////////////////////////////////////////////

  public function loadScripts($filenamesOnly = false, $dir = null) {

    $dir        = $dir ?? Helpers::$settings['js'] ?? '';
    $versioning = Helpers::$settings['versioning'] ?? false;
    $minified   = Helpers::$settings['minify'] ?? false;
    $scripts    = Helpers::$settings['scripts'] ?? [];
    $devmode    = ($this->devmode() ? '?v='.rand() : '');

    $scriptsToLoad = [];

    $scripts = is_array($scripts) ? $scripts : [$scripts];

    // Remove any empty elements
    $scripts = array_filter($scripts);

    if ($versioning) {
      $scriptsToLoad = json_decode(Helpers::$app->versioning->getVersionedNames($scripts));
    } else {
      foreach ($scripts as &$script) {
        if (filter_var($script, FILTER_VALIDATE_URL)) {
          if ($this->devmode()) {
            $scriptsToLoad[] = Helpers::$app->service->params($script, ['v'=>rand()]);
          } else {
            $scriptsToLoad[] = $script;
          }
        } else {
          $scriptsToLoad[] = $dir . $script . $devmode;
        }
      }
      if ( $minified ) {
        $scriptsToLoad = array_map(function($val) { return str_replace('.js', '.min.js', $val); }, $scriptsToLoad);
      }
    }

    if ( !$filenamesOnly ) {
      $scriptTags = [];
      foreach ($scriptsToLoad as &$file) {
        $scriptTags[] = '<script src="'.$dir.$file.$devmode.'"></script>';
      }

      return Template::raw(implode("\n",$scriptTags));

    }

    return json_encode($scriptsToLoad);

  }

  public function getScripts($dir = null) {
    return $this->loadScripts(true, $dir);
  }

  //////////////////////////////////////////////////////////////////////////////
  // Load Stylesheets as per the config.json & settings.php options
  //////////////////////////////////////////////////////////////////////////////

  public function loadStylesheets($filenamesOnly = false) {

    $css        = Helpers::$settings['css'] ?? '';
    $combineCSS = Helpers::$settings['combineCSS'] ?? false;
    $versioning = Helpers::$settings['versioning'] ?? false;
    $devmode    = ($this->devmode() ? '?v='.rand() : '');

    $stylesheetsToLoad = [];

    if ( $combineCSS ) {
      $stylesheets = Helpers::$settings['filenames']['css']['combined'] ?? Helpers::$settings['filenames']['css']['all'] ?? false;
    } else {
      $stylesheets = Helpers::$settings['stylesheets'] ?? false;
    }

    if ( $stylesheets ) {

      $stylesheets = is_array($stylesheets) ? $stylesheets : [$stylesheets];

      foreach ($stylesheets as &$stylesheet) {
        $stylesheetsToLoad[] = $versioning ? Helpers::$app->versioning->addVersionName($stylesheet, 'css', $css) : $stylesheet;
      }

      if ( !$filenamesOnly ) {
        $linkTags = [];
        foreach ($stylesheetsToLoad as &$file) {
          $linkTags[] = '<link rel="stylesheet" href="'.$css.$file.$devmode.'" />';
        }

        return Template::raw(implode("\n",$linkTags));

      }

      return $stylesheetsToLoad;

    }

  }

  public function getStylesheets($dir = null) {
    return $this->loadStylesheets(true, $dir);
  }

  //////////////////////////////////////////////////////////////////////////////
  // Classes
  //////////////////////////////////////////////////////////////////////////////

  public function classes() {

    $element = null;
    $classes = [];

    // Fail if no parameters are passed
    if ( $arguments = func_get_args() ){
      foreach ($arguments as &$setting) {
        if (gettype($setting) == 'object') {
          $element = $setting;
        } else if (gettype($setting) == 'array') {
          $classes = array_merge($setting, $classes);
        } else if (gettype($setting) == 'string') {
          $classes[] = $setting;
        }
      }
    }

    $user = Craft::$app->getUser()->getIdentity() ?? null;

    if (!empty($user) && $user->admin) {
      $classes[] = 'admin';
    }

    if (empty($element)) {
      $element = Helpers::$app->request->getCurrentElement() ?? null;
    }
    if (!empty($element)) {

      $classes[] = $element->slug;

      $classes[] = Helpers::$app->service->getElementType($element);

      // Options to define what data to query for each data type
      $query = [
        'element' => ['id', 'parent', 'child', 'type'],
        'section' => ['type', 'handle'],
        'group' => ['type', 'handle']
      ];

      // If the parent or child is set, also include the levels
      if (!empty($element['parent']) || !empty($element['children'])) {
        if ( $element->level ?? false ) {
          $classes[] = 'level-'.$element->level;
        }
      }

      // Get Entry Information
      foreach ($query['element'] as &$value) {
        if (isset($element[$value])) {
          if (is_numeric($element[$value])) {
            $classes[] = strtolower($value.'-'.$element[$value]);
          } else {
            $classes[] = StringHelper::toKebabCase($element[$value]);
          }
        }
      }

      // Get Section Information
      if (!empty($element['section'])) {
        foreach ($query['section'] as &$value) {
          if (isset($element->section[$value])) {
            if (is_numeric($element->section[$value])) {
              $classes[] = strtolower($value.'-'.$element->section[$value]);
            } else {
              $classes[] = StringHelper::toKebabCase($element->section[$value]);
            }
          }
        }
      }

    }

    // Add each URL segment as a class
    $classes = array_merge(Craft::$app->getRequest()->getSegments(), $classes);

    // Status check
    $status = Craft::$app->getResponse()->getStatusCode();
    $classes[] = $status != 200 ? (((substr($status, 0, 1) != '4' && substr($status, 0, 1) != '5') ? 'status' : 'error'). '-'.$status) : false;

    // Logged In User
    if ( Craft::$app->getUser()->getIdentity() ) {
      $classes[] = 'logged-in';
    }

    // Devmode
    $classes[] = Helpers::$general->devMode ? 'devmode' : null;

    // Environment
    $classes[] = getenv('ENVIRONMENT') ? getenv('ENVIRONMENT').'-environment' : false;

    // Remove any classnames that match the pagename
    $classes = array_diff($classes, [$this->page()]);

    // Clean up classes
    $classes = Helpers::$app->service->sanitiseClasses($classes);

    return $classes;
  }

  //////////////////////////////////////////////////////////////////////////////
  // Page
  //////////////////////////////////////////////////////////////////////////////

  /**
   * If there are no segments in the URL, assume the current page is the homepage
   *
   * @return string
   */
  public function page() {

    $status = Craft::$app->getResponse()->getStatusCode();

    if ( in_array($status, [400, 403, 404, 500]) ) {
      return 'error-'.$status;
    } elseif ($status == 503) {
      return 'holding-page';
    } elseif ($this->homepage()){
      return 'home';
    } elseif ($this->getCurrentElement() ?? false) {
      return $this->getCurrentElement()->slug;
    } else {
      $segments = Craft::$app->getRequest()->getSegments();
      return count($segments) > 1 ? end($segments) : $segments[0];
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Title
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Generate the page title base on a number of variying factors
   *
   * @return string
   */
  public function title() {

    $title = '';

    if (Helpers::$app->query->isDatabaseConnected()) {
      $title = Craft::$app->getSites()->getCurrentSite()->name;
    }

    $status = Craft::$app->getResponse()->getStatusCode();

    if ( isset($this->errors[$status]) ) {
      switch ($status) {
        case 404:
          $title = end(Craft::$app->getRequest()->getSegments());
          $title .= ' '.$this->errors[$status]['type'];
          $title = StringHelper::titleize($title);
          $title = str_replace(['_', '-'], ' ', $title);
        break;
        case 503:
          $title = (empty($title) ? 'This website' : $title).' '.$this->errors[$status]['message'];
        break;
        default:
          $title = $status.' '.$this->errors[$status]['type'];
      }
    } elseif (isset($this->getCurrentElement()->title)) {
      $title = $this->getCurrentElement()->title;
    } elseif ($segments = Craft::$app->getRequest()->getSegments()) {
      $title = end($segments);
      $title = StringHelper::titleize($title);
      $title = str_replace(['_', '-'], ' ', $title);
    }

    return empty($title) ? $_SERVER['SERVER_NAME'] : $title;

  }

  //////////////////////////////////////////////////////////////////////////////
  // Simple requests
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Checks if the devmode is enabled in the config/general settings.
   *
   * @return bool
   */
  public function devmode() {
    return Helpers::$general->devMode === true;
  }


  /**
   * If the httpstatus code is 503, assume this is the holding page
   * @return bool
   */
  public function holdingpage() {
    return Craft::$app->getResponse()->getStatusCode() == 503;
  }

  /**
   * If there are no segments in the URL, assume the current page is the homepage
   *
   * @return bool
   */
  public function homepage() {
    if ( $current = $this->getCurrentElement() ?? false) {
      return $current->uri == '__home__';
    } else {
      return count(Craft::$app->getRequest()->getSegments()) === 0;
    }
  }


  /**
   * Checks to see if a user is logged in, and that use has admin privlidges
   *
   * @return bool
   */
  public function admin() {
    $user = Craft::$app->getUser()->getIdentity();
    return $user && $user->admin;
  }

  /**
   * Defines a session variable if one isn't found and for first time visitors
   * TODO TEST & FIX;
   *
   * @return bool
   */
  public function firstvisit() {

    $firstvisit = Helpers::$app->service->getSession('firstvisit');

    if ( isset($firstvisit) && $firstvisit === true ) {
      return true;
    } else {
      Helpers::$app->service->setSession('firstvisit', true);
      return false;
    }

  }

}

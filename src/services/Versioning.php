<?php

/**
 * Methods requesting and checking data
 */

namespace modules\helpers\services;
use modules\helpers\Helpers;

use Craft;
use craft\base\Component;

class Versioning extends Component {

  //////////////////////////////////////////////////////////////////////////////
  // Versioned Names Checker
  //////////////////////////////////////////////////////////////////////////////

  public function addVersionName($file, $variable, $path = null) {

    $path = is_null($path) ? Helpers::$settings['js'] : $path;
    $minified = Helpers::$settings['minify'] ?? false;
    // Use the version type to get the version number aoociatioed to that env variable
    $versionNumber = getenv(strtoupper(($variable . '_version')));
    $versionedScript = '';

    if (filter_var($file, FILTER_VALIDATE_URL)) {
      $file = Helpers::$app->service->relativeUrl($file);

    } else {

      if (!empty($versionNumber)) {

        // If the version number is found, apply it to the filename
        if (!empty($versionNumber)) {
          $versionedScript = preg_replace('/^([^.]*)(.*)/', '$1.v'.$versionNumber.'$2', $file);
        }

        // Check the versioned filename exists before falling back to the original filename
        if (!empty($versionedScript)) {
          if ($minified && Helpers::$app->request->fileexists($path . '/' . str_replace('.js', '.min.js', $versionedScript))) {
            $file = str_replace('.js', '.min.js', $versionedScript);
          } else if (Helpers::$app->request->fileexists($path . '/' . $versionedScript)) {
            $file = $versionedScript;
          }
        }
      }
    }

    // $file = rtrim($path, '/') . '/' . $file . (Helpers::$app->request->devmode() ? '?v='.rand() : '');
    return $file;
  }

  public function getVersionedNames($files, $path = null) {

    // Loop through each script and apply the javascript file paths. Also include version cache for devmode
    $filesToLoad = [];
    $path = is_null($path) ? Helpers::$settings['js'] : $path;
    $devmode = (Helpers::$app->request->devmode() ? '?v='.rand() : '');

    // Remove any empty elements
    $files = array_filter($files);

    foreach ($files as &$file) {

      $filesToAdd = '';

      // If the script name is an absolute url, just include it as it is
      if (filter_var($file, FILTER_VALIDATE_URL)) {

        $filesToAdd = Helpers::$app->service->relativeUrl($file) . $devmode;

      } else if ( isset(Helpers::$settings['filenames']['js']) ) {

        if ( $variable = array_search($file, Helpers::$settings['filenames']['js']) )  {
          $file = $this->addVersionName($file, $variable, $path);
        } else {
          $file = $file;
        }
        // Add script file without any manipulation to the name
        $filesToAdd = rtrim($path, '/') . '/' . $file . $devmode;
      }

      // Add the script to the list of files to render block
      array_push($filesToLoad, $filesToAdd);
    }

    return json_encode($filesToLoad);
  }


}

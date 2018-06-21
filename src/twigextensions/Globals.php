<?php

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;

use Craft;

class Globals extends \Twig_Extension implements \Twig_Extension_GlobalsInterface {

  public function getGlobals()  {

    // Get settings from site specific and global config/settings.php files
    $globals = Helpers::$settings;

    // Define the title
    $globals['title'] = Helpers::$app->request->title();

    // Add all plugins with their enabled/disabled status
    $globals = array_merge($globals, Helpers::$app->query->getEnabledPlugins());

    return $globals;
  }

}

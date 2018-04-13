<?php

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;

use Craft;

class Globals extends \Twig_Extension implements \Twig_Extension_GlobalsInterface {

  public function getGlobals()  {

    // Get settings from site specific and global config/settings.php files
    $globals = Helpers::$instance->services->getSettings();

    // Add all plugins with their enabled/disabled status
    $globals = array_merge($globals, Helpers::$instance->services->enabledPlugins());

    // Expose the services class to avoid creating function aliases in the variables class.
    $globals['helpers'] = Helpers::$instance->services;

    return $globals;
  }

}

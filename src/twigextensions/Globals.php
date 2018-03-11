<?php

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;

use Craft;

class Globals extends \Twig_Extension implements \Twig_Extension_GlobalsInterface {

    public function getGlobals()  {

        // $globals = Helpers::$instance->littleHelpersModuleService->getSettings();
        // $globals[Helpers::$instance->alias] = Helpers::$instance->littleHelpersModuleService;
        // // $globals['checkers'] = Helpers::$instance->Checkers;
        // $globals['classes'] = Helpers::$instance->littleHelpersModuleService->classes;
        return [];
    }

}

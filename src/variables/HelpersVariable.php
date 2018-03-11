<?php
/**
 * helpers module for Craft CMS 3.x
 *
 * Little helpers to make life a little better
 *
 * @link      https://www.marknotton.uk
 * @copyright Copyright (c) 2018 Mark Notton
 */

namespace modules\helpers\variables;

use modules\helpers\Helpers;

use Craft;

/**
 * @author    Mark Notton
 * @package   HelpersModule
 * @since     1.0.0
 */
class HelpersVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function exampleService($optional = null) {

      $services = Craft::$app->getConfig()->getConfigFromFile('app')['modules']['helpers']['components'];

      return print_r($services, true);
      // return 'test';/
    }

    public function exampleVariable($optional = null)
    {
        $result = "And away we go to the Twig template...";
        if ($optional) {
            $result = "I'm feeling optional today...";
        }
        return $result;
    }
}

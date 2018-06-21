<?php

namespace modules\helpers\variables;

use Craft;
use modules\helpers\Helpers;
use ReflectionClass;


class Variables {

  public $methods = null;

  /**
   * These listens for any methods passed in using the craft.herlpers.XXXX method.
   * Resulting in the ability to call any method in any of the services Classes
   * {{ craft.helpers.classes() }}
   * Note: If you have the same method name in mutiple services classes,
   * only one will be callable.
   */
  public function __call($method, $params) {

    $class = null;

    // Check if there are any componenets we can use
    if ($components = Helpers::$app->components) {

      // Only define the methods function if it hasn't already been defined.
      if ($this->methods === null) {
        // Loop through all available Helper components
        foreach ($components as $component => $path) {
          // Grab the classes in preperation of querying all it's meothods
          $reflection = new \ReflectionClass(Helpers::$app->$component);
          // Define all the class into it's own object
          $this->methods[$component] = $reflection->getMethods();
        }
      }

      // Loop through all method objects
      foreach ($this->methods as $methodName => $methodFunc) {
        // Loop through all method functions
        foreach ($methodFunc as &$item) {
          // Check if a method shares the same name as the mthod trying to be called
          if ($item->name == $method) {
            // Define the classname the method lives in
            $class = $methodName;
            // Break the loop
            break;
          }
        }

        // Break this loop if the class method is found and defined.
        if ( $methodName == $class) {
          break;
        }

      }
    }

    // Call the function
    return !empty($class) ? call_user_func_array([Helpers::$app->$class, $method], $params) : false;
  }

}

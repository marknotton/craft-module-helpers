<?php

namespace modules\helpers\variables;

use modules\helpers\Helpers;

use Craft;

class Variables {

  // Refer to all functions in the services class
  // TODO: Make this include methods from multiple classes: Helpers::$instance->services
  public function __call($method, $params) {
    $methods = array(Helpers::$instance->queries, $method);
    return call_user_func_array($methods , $params );
  }

}

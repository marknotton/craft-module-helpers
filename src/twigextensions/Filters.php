<?php

namespace modules\helpers\twigextensions;

use Craft;

class Filters extends \Twig_Extension {


  public function getFilters() {
    return [
      new \Twig_SimpleFilter('criteria', [$this, 'criteriaFilter']),
    ];
  }

  /**
  * Filter an object of arrays down by specific criteria
  *
  * @example
  * {% set news = craft.entries.section('news').all() %}
  * {% set expired = news|criteria({'status':'expired'}) %}
  *
  * @return array
  */
  public function criteriaFilter(array $array, $criteria)  {
    return array_filter($array, function($array_value) use($criteria) {
      $status = false;
      foreach($criteria as $attribute => $value) {
        $status |= (isset($array_value->$attribute) && $array_value->$attribute == $value);
      }
      return $status;
    });
  }

}

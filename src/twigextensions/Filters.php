<?php

namespace modules\helpers\twigextensions;
use modules\helpers\Helpers;

use Craft;

class Filters extends \Twig_Extension {

  public function getFilters() {
    return [
      new \Twig_SimpleFilter('criteria', [$this, 'criteriaFilter']),
      new \Twig_SimpleFilter('unique', [$this, 'unique']),
      new \Twig_SimpleFilter('json_decode', [$this, 'jsonDecode']),
      new \Twig_SimpleFilter('type', [$this, 'getType']),
      new \Twig_SimpleFilter('count', [$this, 'count'], ['is_safe' => ['html']]),
      new \Twig_SimpleFilter('cleanup', [$this, 'cleanup'], ['is_safe' => ['html']] )
    ];
  }


  public function jsonDecode($data) {
    return json_decode($data);
  }

  public function unique($array)  {
    if ( is_array($array) ) {
      return array_unique($array);
    } else {
      return $array;
    }
  }

  public function getType($variable)  {
    return gettype($variable);
  }

  public function cleanup($data) {
    return Helpers::$app->service->cleanup($data);
  }

  /**
   * Character, Word or Sentence counter
   * @param  string  $string    The original string passed in by the filter
   * @param  string  $delimiter Defaults to 'words'.
   *                            + You can count characters with 'chars'
   *                            + You can count words with 'wrods'
   *                            + You can count sentences with 'sentences'
   * @param  boolean $counter   If this is a number, insteaded of returning the
   *                            char/word/sentence count. A check to see if the count
   *                            is higher than this $counter is true. Returning a boolean.
   * @return int|boolean        Intiger of count, if $counter is not defined. Otherwise Boolean
   */
  public function count($string, $delimiter = 'words', $counter = false) {

    $count = 0;
    $string = strip_tags($string);

    switch ($delimiter) {
      case 'chars':
        $count = strlen($string);
      break;
      case 'words':
        $count = str_word_count($string);
      break;
      case 'sentences':
        preg_match_all("/(^|[.!?])\s*[A-Z]/",$string,$matches);
        $count = count($matches);
      break;
    }

    if ( $counter !== false && is_numeric($counter)) {
      return $count > $counter;
    }

    return (int)$count;

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

<?php

namespace modules\helpers\twigextensions;
use modules\helpers\Helpers;
use craft\helpers\Template;

use Craft;

class Filters extends \Twig_Extension {

  public function getFilters() {
    return [
      new \Twig_SimpleFilter('criteria', [$this, 'criteriaFilter']),
      new \Twig_SimpleFilter('find', [$this, 'findFilter']),
      new \Twig_SimpleFilter('arrayColumn', [$this, 'column']),
      new \Twig_SimpleFilter('getAttributes', [$this, 'getAttributes']),
      new \Twig_SimpleFilter('unique', [$this, 'unique']),
      new \Twig_SimpleFilter('json_decode', [$this, 'jsonDecode']),
      new \Twig_SimpleFilter('type', [$this, 'getType']),
      new \Twig_SimpleFilter('ucfirst', [$this, 'uppercaseFirstWord']),
      new \Twig_SimpleFilter('count', [$this, 'count'], ['is_safe' => ['html']]),
      new \Twig_SimpleFilter('cleanup', [$this, 'cleanup'], ['is_safe' => ['html']] ),
      new \Twig_SimpleFilter('render_text', [$this, 'renderText'], ['is_safe' => ['html']] ),
      new \Twig_SimpleFilter('formatTimeString', [$this, 'formatTimeString'], ['is_safe' => ['html']] ),
      new \Twig_SimpleFilter('dump', [$this, 'dump'])
    ];
  }

  /** @see: http://php.net/manual/en/function.array-column.php */
  public function arrayColumn(array $array, $criteria) {
    return array_column($array, $criteria);
  }

	public function renderText($text) {
		$template = Craft::$app->view->getTwig()->createTemplate($text);
		$template = $template->render([]);
		return Template::raw($template);
	}

  public function jsonDecode($data) {
    return json_decode($data);
  }

  public function dump($data, $die = false) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if ( $die ) {
      die;
    }
  }

  public function unique($array)  {
    if ( is_array($array) ) {
      return array_unique($array);
    } else {
      return $array;
    }
  }

  public function uppercaseFirstWord(string $string)  {
      return ucfirst(strtolower($string));
  }

  public function getType($variable)  {
    return gettype($variable);
  }

	public function formatTimeString($ts) {
		$ts = $ts->format('c');
		    if(!ctype_digit($ts))
		        $ts = strtotime($ts);

		    $diff = time() - $ts;
		    if($diff == 0)
		        return 'now';
		    elseif($diff > 0)
		    {
		        $day_diff = floor($diff / 86400);
		        if($day_diff == 0)
		        {
		            if($diff < 60) return 'Just now';
		            if($diff < 120) return '1 minute ago';
		            if($diff < 3600) return floor($diff / 60) . ' minutes ago';
		            if($diff < 7200) return '1 hour ago';
		            if($diff < 86400) return floor($diff / 3600) . ' hours ago';
		        }
		        if($day_diff == 1) return 'Yesterday';
		        if($day_diff < 7) return $day_diff . ' days ago';
		        if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
		        if($day_diff < 60) return 'Last month';
		        return date('F Y', $ts);
		    }
		    else
		    {
		        $diff = abs($diff);
		        $day_diff = floor($diff / 86400);
		        if($day_diff == 0)
		        {
		            if($diff < 120) return 'In a minute';
		            if($diff < 3600) return 'In ' . floor($diff / 60) . ' minutes';
		            if($diff < 7200) return 'In an hour';
		            if($diff < 86400) return 'In ' . floor($diff / 3600) . ' hours';
		        }
		        if($day_diff == 1) return 'Tomorrow';
		        if($day_diff < 4) return date('l', $ts);
		        if($day_diff < 7 + (7 - date('w'))) return 'next week';
		        if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
		        if(date('n', $ts) == date('n') + 1) return 'next month';
		        return date('F Y', $ts);
		    }
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
  public function criteriaFilter(array $array, $criteria, $matches = true)  {
    if (gettype($criteria) == 'string') {
      return array_filter($array, function($array_value) use($criteria, $matches) {
        if ( $matches === true ) {
          return (isset($array_value, $criteria) || property_exists($array_value, $criteria)) && empty($array_value->$criteria);
        } else {
          return (isset($array_value, $criteria) || property_exists($array_value, $criteria)) && !empty($array_value->$criteria);
        }
      });
    } else {
      return array_filter($array, function($array_value) use($criteria, $matches) {
        foreach($criteria as $attribute => $value) {
          if ( $matches === true ) {
            return (isset($array_value, $attribute) || property_exists($array_value, $attribute)) && $array_value->$attribute == $value;
          } else {
            return (isset($array_value, $attribute) || property_exists($array_value, $attribute)) && $array_value->$attribute != $value;
          }
        }
      });
    }
  }

	/**
  * Filter an object of sections down by specific criteria
  *
	* @param  object             $data     The original string passed in by the filter
	* @param  string|int|object  $criteria string = slug, int = id, object of filter criteria. See criteriaFilter function above.
  *
  * @example
  * Get all entries from the about section
  * {% set about = craft.entries.section('about').all() %}
  * Return the team entry specfically without any additional database queries
  * {% set team = about|find('team') %}
  *
  * @return object
  */

	public function findFilter($data, $criteria) {

		if ( gettype($criteria) == 'string') {
			$key = array_keys($this->criteriaFilter($data, ['slug'=>$criteria]))[0];
		} elseif ( gettype($criteria) == 'integer') {
			$key = array_keys($this->criteriaFilter($data, ['id'=>$criteria]))[0];
		} else {
			$key = array_keys($this->criteriaFilter($data, $criteria))[0];
		}

		return $data[$key];
	}

  /**
  * Filter an object of arrays down by specific criteria
  *
  * @example
  * {% set news = craft.entries.section('news').all() %}
  * {% set expired = news|criteria({'status':'expired'}) %}
  *
  * {% set noImages = news|criteria({'featured':''}, false) %}
  *
  *
  *
  * @return array
  */
  public function criteriaOldFilter(array $array, $criteria, $matches = true)  {
    return array_filter($array, function($array_value) use($criteria, $matches) {
      $status = false;
      foreach($criteria as $attribute => $value) {
        $matchedValue = $matches ? $array_value->$attribute == $value : $array_value->$attribute != $value;
        $status |= (isset($array_value, $attribute) && $matchedValue);
      }
      return $status;
    });
  }

  public function getAttributes(array $array, $key) {
    $attributes = [];

    foreach ($array as &$value) {
      if ( isset($value->$key)) {
        array_push($attributes, $value->$key);
      }
    }

    return !empty($attributes) ? $attributes : null;
  }

}

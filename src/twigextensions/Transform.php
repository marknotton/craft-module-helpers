<?php

namespace modules\helpers\twigextensions;

use Craft;
use modules\helpers\Helpers;

class Transform extends \Twig_Extension {


  public function getFilters() {
    return [
      new \Twig_SimpleFilter('transform', [$this, 'transforms'], ['is_safe' => ['html']]),
    ];
  }

  public function getFunctions():array {
    return [
      new \Twig_SimpleFunction('focalpoint', [$this, 'focalpoint'], ['is_safe' => ['html']]),
      new \Twig_SimpleFunction('focalpoints', [$this, 'focalpoints']),
    ];
  }

  public function focalpoint($data, $property='background-position', $important=false) {
    if ( $focalpoints = $data->focalPoint ?? false ) {
      return $property.':'.($focalpoints['x']*100).'% '.($focalpoints['y']*100).'%'.($important ? ' !important': '').';';
    } else {
      return false;
    }
  }

  // Get the focal point of a dynamically transform image using the focal focalPoints
  // defined in the CMS from the original image.
  public function focalpoints($image, $transform, $percentify=true) {

    $result = ['y' => 0.5, 'x' => 0.5];

    if ( $focalpoints = $image->focalPoint ?? false ) {

      // Height / Y
      if ( $transformHeight = $transform['height'] ?? false ) {
        $originalHeight = $image->height;
        $focalPointY   = $focalpoints['y'];
        $result['y']   =  (($originalHeight / 100 * $focalPointY) * ($transformHeight / 100 * $focalPointY)) / 100 ;
      } else {
        $result['y']   = $focalpoints['y'];
      }

      // Width / X
      if ( $transformWidth = $transform['width'] ?? false ) {
        $originalWidth = $image->width;
        $focalPointX   = $focalpoints['x'];
        $result['x']   = (($originalWidth / 100 * $focalPointX) * ($transformWidth / 100 * $focalPointX)) / 100 ;
        $data = [
          '$transformWidth' => $transformWidth,
          '$originalWidth' => $originalWidth,
          '$focalPointX' => $focalPointX
        ];
        // var_dump($data);
      } else {
        $result['x']   = $focalpoints['x'];
      }
    }

    if ( $percentify ) {
      $result = array_map(function($value) { return $value * 100 . '%'; }, $result);
    }

    return $result;
  }

  // Apply image transofrm to objects or apply image transofrms to any assets used in a WYSIWYG
  public function transforms($data, $transform = null, $checkExistance = true) {

    $image = false;

    if (!is_null($data)) {

      if ($image = $data->getUrl($transform)) {
        if ( $checkExistance ) {
          $image = Helpers::$app->request->fileexists($image);
        }
      }

    }

    return $image;

    // if(is_object($data)) {
    //   $data = $data->getRawContent();
    // }
    // preg_match_all('/\{asset\:(\d+)\:url\}/', $data, $matches);
    // $ecm = craft()->elements->getCriteria(ElementType::Asset);
    // $ecm->id = $matches[1];
    // $assets = $ecm->find();
    //
    // foreach($assets as $key => $asset) {
    //     $data = str_replace('{asset:'.$asset->id.':url}', $this->transform($asset, $transform), $data);
    // }
    // return craft()->elements->parseRefs($data);
  }

  private function transform()	{

    if ( func_num_args() < 1 ) {
      return false;
    }

    $valid = null;

    $arguments = func_get_args();

    $image = $arguments[0];

    $transform = isset($arguments[1]) ? $arguments[1] : null;

		if (is_null($transform) ) {
			return $image;
		}

		// If an object was passed, assume this is asset and query the URL.
		$url = gettype($image) === 'object' ? $image->getUrl($transform) : $image;

    return $url;

	}

}

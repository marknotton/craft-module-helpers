<?php

namespace modules\helpers\twigextensions;

use Craft;
use modules\helpers\Helpers;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use enshrined\svgSanitize\Sanitizer;
use craft\web\twig\Extension;

class Svg extends Extension {

  public function getFunctions():array {
    return [
      new \Twig_SimpleFunction('svg',       [$this, 'svg']),
      new \Twig_SimpleFunction('sprite',    [$this, 'symbol']),
      new \Twig_SimpleFunction('symbol',    [$this, 'symbol']),
      new \Twig_SimpleFunction('svgLegacy', [$this, 'svgFunction']),
    ];
  }

  /**
   * Return the contetns of an SVG. This adds checks and fallbacks to help made
   * defining SVG's more reliable. However, you can use the exact syntax as
   * the Craft's stock svg function too.
   *
   * @param string $svg Add the svg content directly. Or add the filename, with or without a path or extension.
   * @param string $classes Define any number of classes to add to the SVG.
   * @param string $id Define an ID to be added to the SVG.
   * @param bool $sanitize Define an ID to be added to the SVG. Defined 'true' if you want to use the files basename instead
   *
   * @return string
   *
   */
  public function svg() {

    // Fail if no parameters are passed
    if ( func_num_args() < 1 ){
      return false;
    }

		// The first argument is the svg that is automatically passed.
    $svg = (string) func_get_arg(0);

    // Remove the first argument and set the arguments array
    $arguments = array_slice(func_get_args(), 1);

		$sanitize = true;
    $classes  = null;
		$id       = null;

		// Run through the settings and define the appropriate variables
    if ( isset($arguments) ){
      foreach ($arguments as &$setting) {
        if (gettype($setting) == 'boolean') {
          $sanitize = $setting;
        } elseif (gettype($setting) == 'string') {
          if ( $classes == null ) {
            $classes = $setting;
          } elseif ( $id == null ) {
            $id = $setting;
          }
        }
      }
    }

    if (stripos($svg, '<svg') === false) {

      $assetReference = false;

      if (strstr(ltrim($svg, '/'), '/')) {
        $assetReference = true;
        $svg = ltrim($svg, '/');
      }

      $sprites = $assetReference !== true ? ltrim(Helpers::$settings['sprites'] ?? 'assets/images/sprites/', '/') : '';
      $images = $assetReference !== true ? ltrim(Helpers::$settings['images'] ?? 'assets/images/', '/') : '';

      $filename = basename($svg, ".svg");
      $svg = (strlen($svg) > 4 && substr($svg, -4) == '.svg') ? $svg : $svg.'.svg';
      $svg = Craft::getAlias($svg);
      if (!is_file($svg) && !is_file($sprites . '/' . $svg) && !is_file($images . '/' . $svg)) {
        return false;
      }

      if (is_file($svg)) {
        $svg = $svg;
      } elseif ( is_file($sprites . '/' . $svg)) {
        $svg = $sprites . '/' . $svg;
      } elseif ( is_file($images . '/' . $svg)) {
        $svg = $images . '/' . $svg;
      };

      $svg = file_get_contents($svg);

    }


    if ( !empty($classes) || !empty($id) ) {

      $dom = new \DomDocument();
      $dom->loadXML($svg);

      foreach ($elements = $dom->getElementsByTagName('svg') as $i=>$element) {

        if (!empty($classes)) {

          // Check is there are any existing classes and add to them
          if ($existingClasses = $element->getAttribute('class')) {

            // Array of classes to add
            $newClasses = Helpers::$app->service->sanitiseClasses($classes);

            // Array of existing classes
            $existingClasses = Helpers::$app->service->sanitiseClasses($existingClasses);

            // Set the class attribute, whilst excluding any duplicates
            $element->setAttribute('class', Helpers::$app->service->sanitiseClasses($newClasses.' '.$existingClasses));

          } else {

            $element->setAttribute('class', Helpers::$app->service->sanitiseClasses($classes));

          }
        }

        if (!empty($id)) {
          // Add or overwright an existing ID to the first SVG element only
          if ( $i == 0) {
            $element->setAttribute('id', rtrim($id));
          } else {
            $element->setAttribute('id', rtrim(StringHelper::randomString(10).'-'.$id));
          }
        }

        // Defined XML and remove XML Tag
        $svg = $dom->saveXML();

      }
    }

    // Sanitize?
    if ($sanitize) {
      $svg = (new Sanitizer())->sanitize($svg);

      // Remove the style declarations
      $svg = preg_replace('/<style((.|\n|\r)*?)<\/style>/', '', $svg);
    }

    // Remove the XML declaration
    $svg = preg_replace('/<\?xml.*?\?>/', '', $svg);

    return Template::raw($svg);

  }

  /**
   * Add a SVG Symbol
   *
   * @param string $svg Add the svg content directly. Or add the filename, with or without a path or extension.
   * @param string|bool $classes Define any number of classes to add to the SVG. Defined 'true' if you want to use the files basename instead
   * @param string|bool $id Define an ID to be added to the SVG. Defined 'true' if you want to use the files basename instead
   * @param string|bool $autoPrefix Prefix the symbol string with the 'prefix' setting defined in the config.json (filesnames > svg > prefix)
   *
   * @return string
   *
   */
  public function symbol(string $symbol, $classes = true, $id = null, $autoPrefix = true) {

    if ( !empty($autoPrefix) ) {
      $prefix = isset(Helpers::$settings['filenames']['svg']['prefix']) ? Helpers::$settings['filenames']['svg']['prefix'].'-' : 'icon-';
      if (substr($symbol, 0, strlen($prefix)) == $prefix) {
        $symbol = $prefix.substr($symbol, strlen($prefix));
      } else {
        $symbol = $prefix.$symbol;
      }
    }

    if ( !empty($classes) ) {
       $classes = $classes === true ? $symbol : Helpers::$app->service->sanitiseClasses($classes);
       $classes = ' class="'.$classes.'"';
    }

    if ( !empty($id) ) {
       $id = ' id="'.($id === true ? $symbol : $id).'"';
    }

    $symbol = '<svg'.$classes.$id.'><use xlink:href="#'.$symbol.'"></use></svg>';

    return Template::raw($symbol);
  }

}

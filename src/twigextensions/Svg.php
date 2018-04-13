<?php

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;
use craft\helpers\ElementHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\Template as TemplateHelper;
use enshrined\svgSanitize\Sanitizer;
use craft\web\twig\Extension;
use Craft;

class Svg extends Extension {

  public function __construct(){ }
  public function getGlobals() { return []; }

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('svg', [$this, 'svg']),
      new \Twig_SimpleFunction('sprite', [$this, 'symbol']),
      new \Twig_SimpleFunction('symbol', [$this, 'symbol']),
      new \Twig_SimpleFunction('svgLegacy', [$this, 'svgFunction']),
    ];
  }


  /**
   * Return the contetns of an SVG
   * Remove numbers from the start. Lower case.
   *
   * @param string $svg Add the svg content directly. Or add the filename, with or without a path or extension.
   * @param string|bool $classes Define any number of classes to add to the SVG. Defined 'true' if you want to use the files basename instead
   * @param string|bool $id Define an ID to be added to the SVG. Defined 'true' if you want to use the files basename instead
   * @param bool $sanitize Define an ID to be added to the SVG. Defined 'true' if you want to use the files basename instead
   *
   * @return string
   *
   */
  public function svg(string $svg, $classes = null, $id = null, bool $sanitize = true) {

    if (stripos($svg, '<svg') === false) {

      $sprites = ltrim(Helpers::$instance->settings['sprites'], '/') ?? 'assets/images/sprites/' ;
      $images = ltrim(Helpers::$instance->settings['images'], '/') ?? 'assets/images/' ;
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

    // If classes or Id is passed with 'true', the refer to the filename as it's class or id name
    $classes = $classes === true && !empty($filename) ? $filename : $classes;
    $id = $id === true && !empty($filename) ? $filename : $id;

    if ( !empty($classes) || !empty($id) ) {

      $dom = new \DomDocument();
      $dom->loadXML($svg);

      foreach ($elements = $dom->getElementsByTagName('svg') as $i=>$element) {

        if (!empty($classes)) {
          // Check is there are any existing classes and add to them
          if ($existingClasses = $element->getAttribute('class')) {

            // Array of classes to add
            $newClasses = Helpers::$instance->services->sanitiseClasses($classes);

            // Array of existing classes
            $existingClasses = Helpers::$instance->services->sanitiseClasses($existingClasses);

            // Set the class attribute, whilst excluding any duplicates
            $element->setAttribute('class', Helpers::$instance->services->sanitiseClasses($newClasses.' '.$existingClasses));

          } else {

            $element->setAttribute('class', Helpers::$instance->services->sanitiseClasses($classes));

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
    }

    // Remove the XML declaration
    $svg = preg_replace('/<\?xml.*?\?>/', '', $svg);

    return TemplateHelper::raw($svg);

  }


  /**
   * Add a SVG Symbol
   *
   * @param string $svg Add the svg content directly. Or add the filename, with or without a path or extension.
   * @param string|bool $classes Define any number of classes to add to the SVG. Defined 'true' if you want to use the files basename instead
   * @param string|bool $id Define an ID to be added to the SVG. Defined 'true' if you want to use the files basename instead
   *
   * @return string
   *
   */
  public function symbol(string $symbol, $classes = true, $id = null) {

    if ( !empty($classes) ) {

       $classes = $classes === true ? $symbol : Helpers::$instance->services->sanitiseClasses($classes);
       $classes = ' class="'.$classes.'"';
    }

    if ( !empty($id) ) {
       $id = ' id="'.($id === true ? $symbol : $id).'"';
    }

    $symbol = '<svg'.$classes.$id.'><use xlink:href="#'.$symbol.'"></use></svg>';

    return TemplateHelper::raw($symbol);
  }

}

<?php
/**
 * helpers module for Craft CMS 3.x
 *
 * Little helpers to make life a little better
 *
 * @link      https://www.marknotton.uk
 * @copyright Copyright (c) 2018 Mark Notton
 */

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;

use Craft;

/**
 * @author    Mark Notton
 * @package   HelpersModule
 * @since     1.0.0
 */
class HelpersTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Helpers';
    }

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('someFilter', [$this, 'someInternalFunction']),
            // new \Twig_SimpleFilter('clenup', [$this, 'cleanup', array('is_safe' => array('html')]),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('someFunction', [$this, 'someInternalFunction']),
        ];
    }

    /**
     * @param null $text
     *
     * @return string
     */
    public function someInternalFunction($text = null)
    {
        $result = $text . " in the way";

        return $result;
    }

    public function cleanup($data) {

      if(is_object($data)) {
        $data = $data->getParsedContent();
      }

      $regex = '~<((?!iframe|canvas)\w+)[^>]*>(?>[\p{Z}\p{C}]|<br\b[^>]*>|&(?:(?:nb|thin|zwnb|e[nm])sp|zwnj|#xfeff|#xa0|#160|#65279);|(?R))*</\1>~iu';

      return preg_replace($regex, '', $data);

    }
}

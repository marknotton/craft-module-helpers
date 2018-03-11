<?php
/**
 * helpers module for Craft CMS 3.x
 *
 * Little helpers to make life a little better
 *
 * @link      https://www.marknotton.uk
 * @copyright Copyright (c) 2018 Mark Notton
 */

namespace modules\helpers\assetbundles\Helpers;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Mark Notton
 * @package   HelpersModule
 * @since     1.0.0
 */
class HelpersAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@modules/helpers/assetbundles/helpers/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/HelpersModule.js',
        ];

        $this->css = [
            'css/HelpersModule.css',
        ];

        parent::init();
    }
}

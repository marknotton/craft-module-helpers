<?php
/**
 * Helpers module for Craft CMS 3.x
 *
 * fgdfgdg
 *
 * @link      www.marknotton.uk
 * @copyright Copyright (c) 2018 Mark Notton
 */

namespace modules\helpers\assets;

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
        $this->sourcePath = "@modules/helpers/assets";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'scripts/HelpersModule.js',
        ];

        parent::init();
    }
}

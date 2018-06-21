<?php

namespace modules\helpers\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class VideoAssets extends AssetBundle {

  public function init() {
    $this->sourcePath = "@helpers/assets";

    $this->depends = [
      CpAsset::class,
    ];

    $this->js = [
      'scripts/fields/video.js',
    ];

    $this->css = [
      'css/fields/video.css',
    ];

    parent::init();
  }
}

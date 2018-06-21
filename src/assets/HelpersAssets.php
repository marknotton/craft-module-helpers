<?php

namespace modules\helpers\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use modules\helpers\Helpers;

class HelpersAssets extends AssetBundle {

  public function init() {
    $this->sourcePath = "@helpers/assets";

    $this->depends = [
      CpAsset::class,
    ];

    $this->css = [];

    $this->js = ['scripts/helpers.js'];

    if (Helpers::$settings['cms']['themed'] ?? false) {
      array_push($this->css, 'css/themer.css');
      array_push($this->js, 'scripts/themer.js');
    }
    if (Helpers::$settings['cms']['sidebar'] ?? true) {
      array_push($this->js, 'scripts/sidebar.js');
      array_push($this->css, 'css/sidebar.css');
    }

    parent::init();
  }
}

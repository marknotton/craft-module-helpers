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

    $this->js = [];

    $user = Craft::$app->getUser()->getIdentity() ?? null;

    if (getenv('ENVIRONMENT') == 'dev' && !empty($user) && $user->admin && (Helpers::$settings['cms']['template-maker'] ?? false)) {
      array_push($this->css, 'css/template-maker.css');
      array_push($this->js, 'scripts/template-maker.js');
    }

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

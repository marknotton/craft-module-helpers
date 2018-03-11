<?php

namespace modules\helpers;

use modules\helpers\assetbundles\helpers\HelpersAsset;
use modules\helpers\services\Checkers;
use modules\helpers\services\Tests;

use modules\helpers\variables\HelpersVariable;

use modules\helpers\twigextensions\HelpersTwigExtension;
use modules\helpers\twigextensions\Globals;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\TemplateEvent;
use craft\i18n\PhpMessageSource;
use craft\web\View;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;

class Helpers extends Module {

  public static $instance;
  public $twig;
  public $current;
  public $configs = [];
  public $alias = 'helpers';
  public $name = 'Helpers';

  public function __construct($id, $parent = null, array $config = [])  {

    Craft::setAlias('@modules/helpers', $this->getBasePath());

    $i18n = Craft::$app->getI18n();
    if (!isset($i18n->translations[$id]) && !isset($i18n->translations[$id.'*'])) {
      $i18n->translations[$id] = [
        'class' => PhpMessageSource::class,
        'sourceLanguage' => 'en-US',
        'basePath' => '@modules/helpers/translations',
        'forceTranslation' => true,
        'allowOverrides' => true,
      ];
    }

    static::setInstance($this);

    parent::__construct($id, $parent, $config);
  }


  public function init()  {

    parent::init();
    self::$instance = $this;
    $this->twig = Craft::$app->view->getTwig();


    if (Craft::$app->getRequest()->getIsCpRequest()) {
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_TEMPLATE,
            function (TemplateEvent $event) {
                try {
                    Craft::$app->getView()->registerAssetBundle(HelpersAsset::class);
                } catch (InvalidConfigException $e) {
                    Craft::error(
                        'Error registering AssetBundle - '.$e->getMessage(),
                        __METHOD__
                    );
                }
            }
        );
    }

    Craft::$app->view->registerTwigExtension(new HelpersTwigExtension());
    Craft::$app->view->registerTwigExtension(new Globals());

    // Add CSRF Token information
    $csrfTokenName = Craft::$app->getConfig()->getGeneral()->csrfTokenName;
    $csrfTokenValue = Craft::$app->getRequest()->getCsrfToken();

    $js = 'window.csrfTokenName = "'.$csrfTokenName.'"; ';
    $js .= 'window.csrfTokenValue = "'.$csrfTokenValue.'";';

    Craft::$app->getView()->registerJs($js);

    Event::on(
        UrlManager::class,
        UrlManager::EVENT_REGISTER_SITE_URL_RULES,
        function (RegisterUrlRulesEvent $event) {
            $event->rules['siteActionTrigger1'] = 'helpers/default';
        }
    );

    Craft::info(
      Craft::t(
        'helpers',
        '{name} loaded',
        ['name' => 'Helpers']
      ),
      __METHOD__
    );
  }

}

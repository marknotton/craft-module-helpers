<?php

namespace modules\helpers;

use modules\helpers\assets\HelpersAsset;

use modules\helpers\services\Services;
use modules\helpers\services\Queries;

use modules\helpers\variables\Variables;
use modules\helpers\twigextensions\Filters;
use modules\helpers\twigextensions\Svg;
use modules\helpers\twigextensions\Wrapper;
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
  public $settings;
  public $databaseConnected;

  public function __construct($id, $parent = null, array $config = [])  {

    Craft::setAlias('@modules/helpers', $this->getBasePath());
    Craft::setAlias('@helpers', $this->getBasePath());

    $this->controllerNamespace = 'modules\helpers\controllers';

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


    Event::on(
      CraftVariable::class,
      CraftVariable::EVENT_INIT,
      function (Event $event) {
        /** @var CraftVariable $variable */
        $variable = $event->sender;
        $variable->set('helpers', Variables::class);
      }
    );

    $this->databaseConnected = Helpers::$instance->queries->databaseConnected();

    Craft::$app->view->registerTwigExtension(new Filters());
    Craft::$app->view->registerTwigExtension(new Svg());
    Craft::$app->view->registerTwigExtension(new Wrapper());
    Craft::$app->view->registerTwigExtension(new Globals());

    $generalConfig = Craft::$app->getConfig()->getGeneral();
    if ( $generalConfig->enableCsrfProtection !== false ) {

      // Add CSRF Token information
      $csrfTokenName = $generalConfig->csrfTokenName;
      $csrfTokenValue = Craft::$app->getRequest()->getCsrfToken();

      $js = 'window.csrfTokenName = "'.$csrfTokenName.'"; ';
      $js .= 'window.csrfTokenValue = "'.$csrfTokenValue.'";';

      Craft::$app->getView()->registerJs($js, View::POS_HEAD);

    }

    $this->twig = Craft::$app->view->getTwig();
    $this->addTests($this->twig);
    // $this->tests($this->twig);


    // Register our site routes
    Event::on(
      UrlManager::class,
      UrlManager::EVENT_REGISTER_SITE_URL_RULES,
      function (RegisterUrlRulesEvent $event) {
        $event->rules['fetch-template'] = 'helpers/fetch/template';
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

  // TODO: Find a way to move this into it's own file.
  private function addTests($twig) {
    // Is String
    $twig->addTest(new \Twig_SimpleTest('string', function ($value) {
      return is_string($value);
    }));

    // Is Number
    $twig->addTest(new \Twig_SimpleTest('number', function ($value) {
      return is_numeric($value) && (intval($value) == $value || floatval($value) == $value);
    }));

    // Is blank - is defined and is not empty
    $twig->addTest(new \Twig_SimpleTest('blank', function ($value) {
      return empty($value) || strlen(trim($value)) === 0;
    }));

    // Is Array
    $twig->addTest(new \Twig_SimpleTest('array', function ($value) {
      return is_array($value);
    }));

    // Is Object
    $twig->addTest(new \Twig_SimpleTest('object', function ($value) {
      return is_object($value);
    }));

    // Is URL
    $twig->addTest(new \Twig_SimpleTest('url', function ($value) {
      return filter_var($value, FILTER_VALIDATE_URL);
    }));
  }

}

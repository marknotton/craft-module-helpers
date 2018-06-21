<?php

namespace modules\helpers;

use modules\helpers\assets\HelpersAssets;

use modules\helpers\services\Services;
use modules\helpers\services\Requests;
use modules\helpers\services\Queries;

use modules\helpers\variables\Variables;

use modules\helpers\fields\Video;

use modules\helpers\twigextensions\Filters;
use modules\helpers\twigextensions\Svg;
use modules\helpers\twigextensions\Snip;
use modules\helpers\twigextensions\Checks;
use modules\helpers\twigextensions\Transform;
use modules\helpers\twigextensions\Tokens;
use modules\helpers\twigextensions\Wrapper;
use modules\helpers\twigextensions\Globals;
use Craft;
use craft\i18n\PhpMessageSource;

use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;

use craft\services\Fields;
use craft\web\View;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\web\NotFoundHttpException;

class Helpers extends Module {

  public static $app;
  public static $twig;
  public static $settings;
  public static $config;
  public static $general;
  public static $database;
  public static $element;

  //////////////////////////////////////////////////////////////////////////////
  // Construct
  //////////////////////////////////////////////////////////////////////////////

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
    // Base template directory
    Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
      if (is_dir($baseDir = $this->getBasePath().DIRECTORY_SEPARATOR.'templates')) {
        $e->roots[$this->id] = $baseDir;
      }
    });

    static::setInstance($this);

    parent::__construct($id, $parent, $config);
  }

  //////////////////////////////////////////////////////////////////////////////
  // Init
  //////////////////////////////////////////////////////////////////////////////

  public function init()  {

    parent::init();

    $view = Craft::$app->view;
    self::$app      = $this;
    self::$twig     = $view->getTwig();
    self::$general  = Craft::$app->getConfig()->getGeneral();
    self::$config   = Helpers::$app->request->getConfig();
    self::$settings = Helpers::$app->request->getSettings();
    self::$element  = Helpers::$app->request->getCurrentElement();
    self::$database = Helpers::$app->query->isDatabaseConnected();

    Event::on(
      CraftVariable::class,
      CraftVariable::EVENT_INIT,
      function (Event $event) {
        /** @var CraftVariable $variable */
        $variable = $event->sender;
        $variable->set('helpers', Variables::class);
      }
    );

    Event::on(
      Fields::class,
      Fields::EVENT_REGISTER_FIELD_TYPES,
      function (RegisterComponentTypesEvent $event) {
        $event->types[] = Video::class;
      }
    );

    // Add versioning class if versioning is enabled in the config.json
    if ( self::$config['versioning'] ?? false ) {
      self::$app->setComponents([
        'versioning' => \modules\helpers\services\Versioning::class
      ]);
    }

    $view->registerTwigExtension(new Filters($view,   Helpers::$twig));
    $view->registerTwigExtension(new Svg($view,       Helpers::$twig));
    $view->registerTwigExtension(new Wrapper($view,   Helpers::$twig));
    $view->registerTwigExtension(new Snip($view,      Helpers::$twig));
    $view->registerTwigExtension(new Tokens($view,    Helpers::$twig));
    $view->registerTwigExtension(new Transform($view, Helpers::$twig));
    $view->registerTwigExtension(new Checks($view,    Helpers::$twig));
    $view->registerTwigExtension(new Globals($view,   Helpers::$twig));


    // Run these within the CMS backend. Not the frontend.
    if (Craft::$app->getRequest()->getIsCpRequest()) {
      Event::on(
        View::class,
        View::EVENT_BEFORE_RENDER_TEMPLATE,
        function (TemplateEvent $event) {
          try {
            Craft::$app->getView()->registerAssetBundle(HelpersAssets::class);
          } catch (InvalidConfigException $e) {
            Craft::error(
              'Error registering AssetBundle - '.$e->getMessage(),
              __METHOD__
            );
          }
        }
      );

      Helpers::$app->service->themer();

      Helpers::$app->service->installation();

    } else {
      // Run these only in the frontend

      // In instances where the directory structure matches the uri to a disabled page in the CMS,
      // non-admins should be reirected to a 404. Otherwise disabled pages would be accessible.
      $element = Helpers::$app->request->getCurrentElement() ?? false;
      if ( !empty($element) && $element->status == 'disabled' && !Helpers::$app->request->admin() ) {
        throw new NotFoundHttpException('You do not have access to this page. It may be disabled in the CMS.');
      }

    }

    // Register site routes
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

}

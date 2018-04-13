<?php

namespace modules\helpers\services;

use modules\helpers\Helpers;

use Craft;
use craft\base\Component;
use craft\web\twig\variables\Rebrand;

class Queries extends Component {

  public $rebrand;
  private $general;

  public function init() {
    $this->rebrand = Craft::$app->getEdition() != '0' ? new Rebrand() : false;
    $this->general = Craft::$app->config->getGeneral();
  }

  /**
   * Get module files releative from the modules/helpers/assets directory
   *
   * @return string
   */
  public function asset($file, $relative = true)  {
    $url = \Craft::$app->assetManager->getPublishedUrl("@helpers/assets/", true, $file);
    if ($relative === true) {
      $url = Helpers::$instance->services->relativeUrl($url);
    }
    return $url;
  }

  /**
   * Check if the database is connected
   *
   * @return bool
   */
  public function databaseConnected()  {
    try {
      Craft::$app->db->createCommand("SELECT version FROM ".getenv('DB_TABLE_PREFIX')."info")->queryAll();
      return true;
    }
    catch(\yii\db\Exception $exception) {
      return false;
    }
  }

  /**
   * Get the sites brand assets (icon and logo)
   *
   * @return array
   */
  public function rebrand($relative = true)  {

    $rebrand = false;

    if ($this->rebrand) {

      $logo = strtok($this->rebrand->getLogo()->getUrl(), '?');
      $icon = strtok($this->rebrand->getIcon()->getUrl(), '?');

      if ($relative === true) {
        $logo = Helpers::$instance->services->relativeUrl($logo);
        $icon = Helpers::$instance->services->relativeUrl($icon);
      }

      $rebrand = ['logo' => $logo, 'icon' => $icon];

    }
    return $rebrand;
  }


  /////////////////////////////////////////////////////////////////////////////
  // Request functions
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Checks if the devmode is enabled in the config/general settings.
   *
   * @return bool
   */
  public function devmode() {
    return $this->general->devMode === true;
  }

  /**
   * Get the current uri segments
   *
   * @return array
   */
  public function segments() {
    return Craft::$app->getRequest()->getSegments();
  }

  /**
   * Gets the current httpstatus code
   *
   * @return number
   */
  public function status() {
    return Craft::$app->getResponse()->getStatusCode();
  }

  /**
   * If the httpstatus code is 503, assume this is the holding page
   * @return bool
   */
  public function holdingpage() {
    return $this->status() == 503;
  }

  /**
   * If there are no segments in the URL, assume the current page is the homepage
   *
   * @return bool
   */
  public function homepage() {
    return count($this->segments()) === 0;
  }

  /**
   * Checks to see if a user is logged in, and that use has admin privlidges
   *
   * @return bool
   */
  public function admin() {
    $user = Craft::$app->getUser()->getIdentity();
    return $user && $user->admin;
  }

}

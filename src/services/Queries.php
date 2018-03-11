<?php

namespace modules\helpers\services;

use modules\helpers\Helpers;

use Craft;
use craft\base\Component;

class Queries extends Component {

  public function init() {

  }

  /**
   * Checks if the devmode is enabled in the config/general settings.
   *
   * @return bool
   */
  public function devmode() {
    return Craft::$app->getConfig()->general->devMode === true;
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
   * Check if the user is on a mobile or tablet device.
   *
   * @param bool[true] Set true if you want to include tablets
   *
   * @return bool
   */
  public function isMobile($includeTablets = true) {
    return Craft::$app->getRequest()->isMobileBrowser($includeTablets);
  }

  /**
   * Check wether the user agent is a robot
   *
   * @return bool
   */
  public function robot() {
    return false;
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
   * Determines how the current entry was loaded. AJAX, Fetch, or standard HTTP
   *
   * @return string
   */
  public function request() {
    return 'standard';
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

  /**
   * Defines a session variable if one isn't found and for first time visitors
   *
   * @return bool
   */
  public function firstvisit() {

    $firstvisit = $this->getSession('firstvisit');

    if ( isset($firstvisit) && $firstvisit === true ) {
      return true;
    } else {
      $this->setSession('firstvisit', true);
      return false;
    }

  }

}

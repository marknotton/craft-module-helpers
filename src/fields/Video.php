<?php
/**
* helpers module for Craft CMS 3.x
*
* ssdg
*
* @link      www.marknotton.uk
* @copyright Copyright (c) 2018 mark
*/

namespace modules\helpers\fields;

use modules\helpers\Helpers;
use modules\helpers\assets\VideoAssets;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;
use Stringy\Stringy;

class Video extends Field {

  public static function displayName(): string {
    return Craft::t('helpers', 'Video');
  }

  //////////////////////////////////////////////////////////////////////////////
  // Available Formats
  // ---------------------------------------------------------------------------
  // To add more video formats, you should only need to add to this array.
  // Everything else will be managed automatically
  //////////////////////////////////////////////////////////////////////////////

  public $formats = [
    'youtube' => [
      'name' => 'YouTube',
      'regex' => '/\/\/(?:www\.)?youtu(?:\.be|be\.com)\/(?:watch\?v=|embed\/)?([a-z0-9_\-]+)/i',
    ],
    'vimeo' => [
      'name' => 'Vimeo',
      'regex' => '/\/\/(?:www\.)?vimeo.com\/([0-9a-z\-_]+)/i',
    ],
    'dailymotion' => [
      'name' => 'Dailymotion',
      'regex' => '/^.+dailymotion.com\/(?:video|hub)\/([^_]+)[^#]*(#video=([^_&]+))?/',
    ]
  ];

  // Regex to validate a URL
  public $urlRegex = '/(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/';


  //////////////////////////////////////////////////////////////////////////////
  // Get Settings
  //////////////////////////////////////////////////////////////////////////////

  public function getSettingsHtml()  {

    $settings['field'] = $this;

    // Render the settings template
    return Craft::$app->getView()->renderTemplate('helpers/_components/fields/video/settings', $settings);
  }


  //////////////////////////////////////////////////////////////////////////////
  // Input HTML
  //////////////////////////////////////////////////////////////////////////////

  public function getInputHtml($value, ElementInterface $element = null): string
  {
    // Register our asset bundle
    Craft::$app->getView()->registerAssetBundle(VideoAssets::class);

    // Get our id and namespace
    $id = Craft::$app->getView()->formatInputId($this->handle);
    $namespace = Craft::$app->getView()->namespaceInputId($id);

    // Variables to pass down to our field JavaScript to let it namespace properly
    $jsonVars = [
      'id' => $id,
      'name' => $this->handle,
      'namespace' => $namespace,
      'settings' => $this->getSettings()
    ];
    $jsonVars = Json::encode($jsonVars);
    Craft::$app->getView()->registerJs("$('#{$namespace}-field').video(" . $jsonVars . ");");

    if((new Stringy($value))->isJson($value)) {
      $value = json_decode($value);
    }
    elseif(!$element->hasErrors($this->handle)) {
      $value = $this->serializeValue($value);
    }

    // Render the input template
    return Craft::$app->getView()->renderTemplate(
      'helpers/_components/fields/video/input',
      [
        'name' => $this->handle,
        'value' => $value,
        'field' => $this,
        'id' => $id,
        'namespace' => $namespace,
      ]
    );
  }

  //////////////////////////////////////////////////////////////////////////////
  // Get thumbnails
  //////////////////////////////////////////////////////////////////////////////

  public function getThumbnails($format, $code) {
    // Poster/Thumbnail information & link
    if (!empty($format) && !empty($code)) {

      $poster = false;
      $link   = false;

      switch ($format) {
        case 'youtube':
          $poster = array(
            'small' => 'https://img.youtube.com/vi/'.$code.'/0.jpg',
            'medium' => 'https://img.youtube.com/vi/'.$code.'/1.jpg',
            'large' => 'https://img.youtube.com/vi/'.$code.'/maxresdefault.jpg',
          );
          $link = 'https://www.youtube.com/watch?v=' . $code;
        break;
        case 'vimeo':
          $hash = unserialize(file_get_contents('https://vimeo.com/api/v2/video/'.$code.'.php'));
          $poster = array(
            'small' => $hash[0]['thumbnail_small'],
            'medium' => $hash[0]['thumbnail_medium'],
            'large' => $hash[0]['thumbnail_large'],
          );
          $link = 'https://vimeo.com/' . $code;
        break;
        case 'dailymotion':
          $hash = unserialize(file_get_contents('https://api.dailymotion.com/video/'.$code.'?fields=thumbnail_small_url,thumbnail_medium_url,thumbnail_large_url'));
          $poster = array(
            'small' => $hash[0]['thumbnail_small_url'],
            'medium' => $hash[0]['thumbnail_medium_url'],
            'large' => $hash[0]['thumbnail_large_url'],
          );
          $link = 'http://www.dailymotion.com/video/' . $code;
        break;
      }

      return [$poster, $link];
    } else {
      return null;
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Validate URLS
  //////////////////////////////////////////////////////////////////////////////

  // Validates the URL is an acceptable video format, and is enabled in the field options
	public function validUrl($url) {

		if ( empty($url) ) {
			return true;
		}

		$valid = false;

		foreach($this->formats as $handle => $options) {
			if (preg_match($options['regex'], $url)) {
				$valid = true;
				break;
			}
		}

		return preg_match($this->urlRegex, $url) && $valid;
	}
  //
	// public function validate($attributeNames = NULL, $clearErrors = true) {
  //   return false;
  //   // var_dump($attributeNames); die;
	// 	return isset($value['url']) && $this->validUrl($value['url']) ? true : 'Must be a valid URL';
	// }

  //////////////////////////////////////////////////////////////////////////////
  // TODO: Figure out what these do, and if I need them
  //////////////////////////////////////////////////////////////////////////////

  public $youtube = true;
  public $vimeo = true;
  public $dailymotion = true;

  // public function rules() {
  //
  //   $rules = parent::rules();
  //   $rules = array_merge($rules, [
  //     ['youtube', 'boolean'],
  //     ['vimeo', 'boolean'],
  //     ['dailymotion', 'boolean'],
  //   ]);
  //   return $rules;
  // }

  public function getElementValidationRules(): array
  {
    return ['doStuff', ['string']];
  }
  public function doStuff(ElementInterface $element)
  {
    $value = $element->getFieldValue($this->handle);
    if(!$this->validUrl($value)) {
      $element->addErrors([$this->handle => 'Must be a valid URL']);
    }
  }
  public function getContentColumnType(): string {
    return Schema::TYPE_TEXT;
  }

  public function normalizeValue($value, ElementInterface $element = null) {
    return $value;
  }

  public function serializeValue($value, ElementInterface $element = null) {

    $code   = false;
    $format = false;
    $poster = false;
    $link   = false;
    $embed  = false;

    foreach($this->formats as $format => $options) {
      if (preg_match($options['regex'], $value, $matches) && count($matches) > 1) {
        $code = $matches[1];
        $format = $format;
        break;
      }
    }

    // Poster/Thumbnail information & link
    if (!empty($format) && !empty($code)) {
      switch ($format) {
        case 'youtube':
          $poster = array(
            'small' => 'https://img.youtube.com/vi/'.$code.'/0.jpg',
            'medium' => 'https://img.youtube.com/vi/'.$code.'/1.jpg',
            'large' => 'https://img.youtube.com/vi/'.$code.'/maxresdefault.jpg',
          );
          $link = 'https://www.youtube.com/watch?v=' . $code;
          $embed = 'https://www.youtube.com/embed/' . $code;
        break;
        case 'vimeo':
          $hash = @unserialize(file_get_contents('https://vimeo.com/api/v2/video/'.$code.'.php'));
          $poster = array(
            'small' => $hash[0]['thumbnail_small'],
            'medium' => $hash[0]['thumbnail_medium'],
            'large' => $hash[0]['thumbnail_large'],
          );
          $link = 'https://vimeo.com/' . $code;
          $embed = 'https://player.vimeo.com/video/' . $code;
        break;
        case 'dailymotion':
          $hash = @unserialize(file_get_contents('https://api.dailymotion.com/video/'.$code.'?fields=thumbnail_small_url,thumbnail_medium_url,thumbnail_large_url'));
          $poster = array(
            'small' => $hash[0]['thumbnail_small_url'],
            'medium' => $hash[0]['thumbnail_medium_url'],
            'large' => $hash[0]['thumbnail_large_url'],
          );
          $link = 'http://www.dailymotion.com/video/' . $code;
          $embed = '//www.dailymotion.com/embed/video/' . $code;
        break;
      }
    } else {
      return null;
    }

    // Saves this entry as both the original url and the stripped out video code
    return array(
      'url' => $value,
      'code' => $code,
      'format' => $format,
      'link' => $link,
      'embed' => $embed,
      'poster' => $poster
    );

    // return parent::serializeValue($value, $element);
  }

}

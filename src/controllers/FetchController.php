<?php

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;

class FetchController extends Controller {

  protected $allowAnonymous = ['template'];

  public function actionTemplate() {

    $request = null;
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
      $request = $_SERVER['HTTP_X_REQUESTED_WITH'];
      switch (strtolower($request)) {
        case 'xmlhttprequest':
          $request = "ajax";
        break;
        case 'fetch':
          $request = "fetch";
        break;
        default;
          $request = "standard";
        break;
      }
    }

    // Extract all post paramaters as variables
    $data = $request === 'ajax' ? Craft::$app->getRequest()->getBodyParams() : json_decode(file_get_contents('php://input'));
    extract((array)$data);

    // Default response
    $response = [
      'message' => 'Template was not defined in your '.($request == 'ajax' ? 'data' : 'body').' param',
      'request' => $request,
      'data' => $data
    ];

    if(!empty($template)){

      try{

        $settings = Helpers::$app->request->getSettings();
        $response['success'] = true;
        $response['message'] = 'Template '.$template.' found';

        // If a section key was passed. Assume an entry has attempted to be rendered
        if (!empty($section) && is_string($section)) {
          $settings['section'] = Craft::$app->getSections()->getSectionByHandle($section);
        }

        if (!empty($id)) {
          if ( is_array($id) && count($id) > 1 ) {
            $settings['entries'] = Entry::find()->id($id)->section($section ?? null)->all();
          } else {
            $settings['entry'] = Entry::find()->id($id)->section($section ?? null)->one();
          }
        }

        // These variables will be accessible in the rendered template
        $variables = array_merge($settings, (array)$data);

        $response['html'] = Craft::$app->getView()->renderTemplate($template, $variables);
      } catch(\Exception $e) {

        $response['error'] = true;
        unset($response['success']);
        $response['message'] = $e->getMessage();

      }
    }

    return $this->asJson($response);

  }

}

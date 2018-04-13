<?php

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;

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

        $settings = Helpers::$instance->services->getSettings();
        $response['success'] = true;
        $response['message'] = 'Template '.$template.' found';

        // If a section key was passed. Assume an entry has attempted to be rendered
        if (!empty($section)) {
          $entry = Entry::find()->section($section)->one();
          $settings['entry'] = $entry;
        }

        // These variables will be accessible in the rendered template
        $variables = array_merge($settings, (array)$data);

        $response['html'] = Craft::$app->getView()->renderTemplate($template, $variables);

      } catch(\Twig_Error_Loader $e) {

        $response['error'] = true;
        unset($response['success']);
        $response['message'] = $e->getMessage();

      } catch(\yii\base\Exception $e) {

        $response['error'] = true;
        unset($response['success']);
        $response['message'] = $e->getMessage();

      }
    }
    return $this->asJson($response);

  }

}

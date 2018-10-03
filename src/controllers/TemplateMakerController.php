<?php

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;

class TemplateMakerController extends Controller {

  protected $allowAnonymous = ['template'];

  public function actionDefault() {

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
      'message' => 'Entry Type ID was not defined in your '.($request == 'ajax' ? 'data' : 'body').' param',
      'request' => $request,
      'data' => $data
    ];

    if(!empty($id)){

      try{

        $settings = Helpers::$app->request->getSettings();
        $entryType = Entry::find()->typeId($id)->all()[0];

        $response['success'] = true;
        $response['message'] = 'Entry Type for '.$entryType->title.' found';

        $currentLayout = $entryType->getFieldLayout();
        $currentTabs = $currentLayout->getTabs();

        $postedFieldLayout = [];

        foreach ($currentTabs as $tab) {
          $fields = $tab->getFields();
          foreach ($fields as $field) {
            $postedFieldLayout[$tab->name][] = [
              'name'   => $field->name   ?? false,
              'handle' => $field->handle ?? false,
              'id'     => $field->id     ?? false,
              'type'   => $field->type   ?? false,
              'field'  => $field
            ];
          }
        }

        $response['fields'] = $postedFieldLayout;

      } catch(\Exception $e) {

        $response['error'] = true;
        unset($response['success']);
        $response['message'] = $e->getMessage();

      }
    }

    return $this->asJson($response);

  }

}

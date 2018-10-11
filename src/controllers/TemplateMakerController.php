<?php

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;
use craft\helpers\StringHelper;
use craft\elements\Entry;

class TemplateMakerController extends Controller {

  protected $allowAnonymous = ['template'];

  public function actionTemplateExists() {

    // Extract all post paramaters as variables
    $data = json_decode(file_get_contents('php://input'));
    extract((array)$data);

  }

  public function actionDefault() {

    // Extract all post paramaters as variables
    $data = json_decode(file_get_contents('php://input'));
    extract((array)$data);

    // Default response
    $response = [
      'message' => 'Entry Type ID was not defined in your body param',
      'data' => $data
    ];

    if(!empty($id)){

      try{

        $settings = Helpers::$app->request->getSettings();
        $entryType = Entry::find()->typeId($id)->all()[0];

        $response['success'] = true;
        $response['message'] = 'Entry Type for '.$entryType->title.' found';

        $sectionData = Helpers::$app->query->sectionRouteRules();
        $fieldsData  = Helpers::$app->query->fields();

        $section = $sectionData[array_search($entryType->sectionId, array_column($sectionData, 'id'))];

        $tabs = [];
        $currentLayout = $entryType->getFieldLayout();
        $currentTabs = $currentLayout->getTabs();

        foreach ($currentTabs as $tab) {
          $tabFields = $tab->getFields();
          foreach ($tabFields as $field) {
            $tabs[$tab->name][] = [
              'name'   => $field->name   ?? false,
              'handle' => $field->handle ?? false,
              'id'     => $field->id     ?? false,
              'type'   => $fieldsData[array_search($field->id, array_column($fieldsData, 'id'))]['type'] ?? false
            ];
          }
        }
        Helpers::$app->templateMaker->create($tabs, $section);
        $template = Helpers::$app->templateMaker->create($tabs, $section);

        $response['template'] = $template;
        $response['tabs']     = $tabs;
        $response['section']  = $section;
        // Craft::$app->getSession()->setNotice("Template Created");

      } catch(\Exception $e) {

        $response['error'] = true;
        unset($response['success']);
        $response['message'] = $e->getMessage();

        // Craft::$app->session->setError("Failed to create template");

      }
    }

    return $this->asJson($response);

  }

}

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
    $response = [];

    if(!empty($id)){

      try{

        $sectionData = Helpers::$app->query->sectionRouteRules();
        $fieldsData  = Helpers::$app->query->fields();

        $entryType = Entry::find()->typeId($id)->all()[0];
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

        $response['success'] = true;
        // $response['newTimestamp'] = Helpers::$app->templateMaker->timestamp();

        $creation = Helpers::$app->templateMaker->create([
          'tabs'      => $tabs ?? false,
          'id'        => $id,
          'path'      => $path,
          'template'  => $template,
          'timestamp' => $timestamp
        ]);

        $response = array_merge($response, $creation);

        // Craft::$app->getSession()->setNotice("Template Created");

      } catch(\Exception $e) {

        $response['error'] = true;
        $response['message'] = $e->getMessage();

        // Craft::$app->session->setError("Failed to create template");

      }
    }

    return $this->asJson($response);

  }

}

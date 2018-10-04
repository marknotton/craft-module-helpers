<?php

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;
use craft\helpers\StringHelper;
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
              // 'type' => explode('\\', $fieldsData[array_search($field->id, array_column($fieldsData, 'id'))]['type'])
            ];
          }
        }

        $template = $this->createTemplates($tabs, $section);

        $response['template'] = $template;
        $response['tabs']     = $tabs;
        $response['section']  = $section;

      } catch(\Exception $e) {

        $response['error'] = true;
        unset($response['success']);
        $response['message'] = $e->getMessage();

      }
    }

    return $this->asJson($response);

  }

  private function createTemplates($tabs, $section) {

    // Get contents of a generic template.
    $layout = file_get_contents(Craft::getAlias('@templates').'/_layouts/generic.twig');

    // Define the name of the new template file.
    $newTemplateFileName = 'file.twig';
    $newTemplateFilePath = Craft::getAlias('@templates').'/'.$newTemplateFileName;

    // Create a new file.
    $newTemplate = fopen($newTemplateFilePath, 'w') or die('Cannot open file:  '.$newTemplateFilePath);

    // Exclude these tabs from being generated.
    $tabExclusions = ['seo'];

    // Elements Tags that are valid markup and don't need to be validated.
    $elementExceptions = ['main', 'nav', 'aside', 'header', 'footer', 'article', 'section'];

    // Special Rules
    // TODO: Create specials rules to generate an include for speicficl field handles
    // and also redirect specific field types to a different sample file.
    $sampleAliases = [
      'supercool\tablemaker\fields\TableMakerField' => [
        'alias' => 'TableMaker'
      ],
      'craft\redactor\Field' => [
        'alias' => 'Redactor'
      ],
      'modules\helpers\fields\Video' => [
        'alias' => 'Video'
      ]
    ];

    $fieldIncludes = [
      'featuredImage' => [
        'include' => '_componenets/featured-image',
        'only' => false,
        'with' => []
      ]
    ];

    // Loop through all tabs.
    foreach ($tabs as $tab => $fields) {

      // Kebabify the key name for use as an element tag.
      $element = StringHelper::toKebabCase($tab);

      // Ignore specific tabs.
      if ( !in_array($element, $tabExclusions) ) {

        // If the element name happens to be a valid HTML5 tag, leave it as it is.
        // Otherwise check if at least one hyphen exists. If it doesn't, add one
        // to ensure valid custom element markup.
        // TODO: Above comment

        // Add the correct amount of devider characters for consistency.
        $deviders = str_repeat('=', 80 - (strlen($tab) + 13));

        // Comment line for the tab name.
        $layout .= "\n\t{# ".$tab." Tab ".$deviders." #}\n";

        // Tab open element.
        $layout .= "\n\t<".$element.">\n";

        // Loop through all fields for this tab.
        foreach ($fields as $field) {

          // Turn field type string into array.
          $sampleFile = explode('\\', $field['type']);

          // Define a sample file path for the field type.
          $sampleFile = Craft::getAlias('@helpers').'/templates/_samples/'.$sampleFile[count($sampleFile) - 1].'.twig';

          // If the file exists.
          if (file_exists($sampleFile)) {

            // Add the correct amount of devider characters for consistency.
            $deviders = str_repeat('-', 80 - (strlen($field['name']) + 11));

            // Comment line for the field name.
            $layout .= "\n\t\t{# ".$field['name']." ".$deviders." #}\n";

            // Get sample file contents.
            $fieldContent = file_get_contents($sampleFile);

            // TODO: Add tabs on each line:
            // SEE: https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php

            // Replace any instances of the string 'fieldHandle', and replace it
            // with the relivant fieldHandle.
            $fieldContent = str_replace('fieldHandle', $field['handle'], $fieldContent);

            // Add modified contents to layout.
            $layout .= "\n\t\t".$fieldContent;
          }

        }

        // Tab close element.
        $layout .= "\n\t</".$element.">\n";
      }

    }

    // Write template file.
    fwrite($newTemplate, $layout);

    return [ 'filename' => $newTemplateFileName, 'path' => $newTemplateFilePath];

  }

}

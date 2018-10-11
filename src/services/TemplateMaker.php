<?php

/**
 * Methods for creating dynamic templates
 */

namespace modules\helpers\services;
use modules\helpers\Helpers;

use Craft;
use craft\web\View;
use craft\base\Component;
use craft\helpers\StringHelper;
use craft\helpers\Template;
use craft\elements\Entry;

class TemplateMaker extends Component {

  private $sections;
  private $section;

  public function init() {

    $segments = Craft::$app->getRequest()->getSegments();

    if ( in_array("entrytypes", $segments) ) {

      $id = $segments[array_search('entrytypes', $segments) - 1] ?? false;
      $this->sections = Helpers::$app->query->sectionRouteRules()?? false;
      $this->section = $this->sections[$id - 1] ?? false;
      $templateExists = false;

      if (!empty($this->section) && !empty($id) && is_numeric($id) ) {

        $path = $this->getPath();
        $templateName = $this->getTemplateName();
        $templateFullPath = Craft::getAlias('@templates').'/'.ltrim($path, '/').'/'.ltrim($templateName, '/').'.twig';

        if ( file_exists($templateFullPath) ) {
          $templateExists = true;
          $templateName .= '_'.time();
        }

        $template = Craft::$app->view->renderTemplate("helpers/_components/template-maker/input", ['id' => $id, 'path' => $path, 'template' => $templateName, 'templateExists' => $templateExists]);
        $templateMakerForm = "var templateMaker = '".str_replace(array("\n", "\r"), '', $template)."';";
        Craft::$app->getView()->registerJs($templateMakerForm, View::POS_HEAD);
      }
    }
  }

  private function getPath() {
    $path = StringHelper::toKebabCase(trim(preg_replace('/{.*?\}/m', '', $this->section['uriFormat']),'/').'/' ?? '');
    if ( !in_array($path, ["home", "homepage"]) ) {
      return $path;
    }
  }

  private function getTemplateName() {

    if ( $this->section['template'] == '_loader' || $this->section['template'] == '_loader.twig' ) {
      if ( $this->section['type'] == 'channel' || $this->section['type'] == 'structure' ) {
        return '_entry';
      } else {
        return 'index';
      }
    } else {
      return preg_replace('/\\.[^.\\s]{3,4}$/', '', $this->section['template']);
    }


  }

  public function create($tabs, $section) {

    // Get contents of a generic template.
    $layout = file_get_contents(Craft::getAlias('@templates').'/_layouts/generic.twig');

    // Define the name of the new template file.
    $newTemplateFileName = $section['handle'];

    if ( $section['uriFormat'] == '_loader' || $section['uriFormat'] == '_loader.twig') {
      $uri = StringHelper::toKebabCase(trim(preg_replace('/{.*?\}/m', '', $section['uriFormat']),'/').'/' ?? '');
    } else {
      $uri = $section['uriFormat'];
    }

    if ( $uri == "__home__" ) {
      $uri = "";
      $newTemplateFileName = "home";
    }


    $newTemplateFilePath = Craft::getAlias('@templates').'/'.$uri.$newTemplateFileName.'.twig';

    // Tab indentation count
    $tabIndentationCount = 1;
    $tabIndentation = str_repeat("\t", $tabIndentationCount);

    // Create a new file.
    $newTemplate = fopen($newTemplateFilePath, 'w') or die('Cannot open file:  '.$newTemplateFilePath);

    // Exclude these tabs from being generated.
    $tabExclusions = ['seo'];

    // Elements Tags that are valid markup and don't need to be validated.
    $elementExceptions = ['main', 'nav', 'aside', 'header', 'footer', 'article', 'section'];

    // Matching tabs names should be rendered in these block types of they exist.
    $blocks = ['navigation', 'header', 'main', 'content', 'aside', 'footer'];

    // Special Rules
    // TODO: Create specials rules to generate an include for speicficl field handles
    // and also redirect specific field types to a different sample file.
    $fieldAliases = [
      'supercool\tablemaker\fields\TableMakerField' => 'TableMaker',
      'craft\redactor\Field' => 'Redactor',
      'featuredImage' => 'FeaturedImage',
      'body' => 'Body'
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

        // Comment line for the tab name.
        $layout .= "\n".$tabIndentation."{# ".str_repeat('=', 74 - ($tabIndentationCount*2))." #}\n";
        $layout .= $tabIndentation."{# ".$tab." Tab ".str_repeat(' ', 80 - (strlen($tab) + 11) - ($tabIndentationCount*2))." #}\n";
        $layout .= $tabIndentation."{# ".str_repeat('=', 74 - ($tabIndentationCount*2))." #}\n";

        // Tab open element.
        $layout .= "\n".$tabIndentation."<".$element.">\n";

        // Loop through all fields for this tab.
        foreach ($fields as $field) {

          // Turn field type string into array.
          $sampleFile = explode('\\', $field['type']);

          // Define a sample file path for the field type.
          $sampleFile = Craft::getAlias('@helpers').'/templates/_samples/'.$sampleFile[count($sampleFile) - 1].'.twig';

          // If the file exists.
          if (file_exists($sampleFile)) {

            // Add the correct amount of devider characters for consistency.
            $deviders = str_repeat('-', 80 - (strlen($field['name']) + 7) - ($tabIndentationCount*4));

            // Comment line for the field name.
            $layout .= "\n".$tabIndentation.$tabIndentation."{# ".$field['name']." ".$deviders." #}\n";

            // Get sample file contents.
            $fieldContent = file_get_contents($sampleFile);

            // TODO: Add tabs on each line:
            // SEE: https://stackoverflow.com/questions/1462720/iterate-over-each-line-in-a-string-in-php

            // $separator = "\r\n";
            // $line = strtok($fieldContent, $separator);
            //
            // while ($line !== false) {
            //     # do something with $line
            //     $line = strtok( $separator );
            // }


            // Replace any instances of the string 'fieldHandle', and replace it
            // with the relivant fieldHandle.
            $fieldContent = str_replace('fieldHandle', $field['handle'], $fieldContent);
            $fieldContent = str_replace('fieldName', $field['name'], $fieldContent);

            // Add modified contents to layout.
            $layout .= "\n".$tabIndentation.$tabIndentation.$fieldContent;
          }

        }

        // Tab close element.
        $layout .= "\n".$tabIndentation."</".$element.">\n";
      }

    }

    // Write template file.
    fwrite($newTemplate, $layout);

    return [ 'filename' => $newTemplateFileName, 'path' => $newTemplateFilePath.'.twig', 'uri' => $uri];

  }

}

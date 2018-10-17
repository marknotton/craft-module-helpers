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
  private $segments;
  private $section;
  private $tabLength = 2;

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------

  public function init() {

    // Globally define the URL segments
    $this->segments = Craft::$app->getRequest()->getSegments();

    // Checks in the URL to determine if the user is actually on an individual entry type page
    if ( in_array("entrytypes", $this->segments) && array_search("entrytypes", $this->segments) < count($this->segments) - 1 ) {

      // Grab the section ID from the URL. It's the third from the end segment item.
      $sectionId = $this->segments[array_search('entrytypes', $this->segments) - 1];

      // Globally define all sections using Helpers route rules method
      $this->sections = Helpers::$app->query->sectionRouteRules();

      // Globabbly define the current section using the section ID
      $this->section = $this->sections[$sectionId - 1];

      if (!empty($this->section) && !empty($sectionId) && is_numeric($sectionId) ) {

        // Set the path name and and template name.
        $path = $this->pathSanitiser();
        $templateName = $this->templateSantiser();

        // Set a timestamp to be used as a filename suffix should there be a naming conflict.
        $timestamp = time();

        // Get a list of all the files that exist in the templates directory.
        // This will be tested against to instruct users if they are about to
        // overwrite a existing file.
        $existingFiles = json_encode(Helpers::$app->request->getFileDirectory(null)) ?? [];

        // It's unlikely the path name and template name will ever be the same.
        // If this occures, force the template name to '_entry'
        if ( $path == $templateName ) {
          $templateName = '_entry';
        }

        // Define a bunch of data that will passed into the template maker form
        $settings = [
          'id'            => $sectionId,
          'path'          => $path,
          'template'      => $templateName,
          'timestamp'     => $timestamp,
          'existingFiles' => $existingFiles
        ];

        // Render the template-maker form and return the markup.
        $template = Craft::$app->view->renderTemplate("helpers/_components/template-maker/input", $settings);

        // Store the template markup as a string in a Javascript variable
        $templateMakerForm = "var templateMaker = '".str_replace(array("\n", "\r"), '', $template)."';";

        // Render the $templateMakerForm variable into the current entry type page.
        // The 'src/assets/scripts/tempalte-maker.js' will append the form to the bottom of the page.
        Craft::$app->getView()->registerJs($templateMakerForm, View::POS_HEAD);
      }
    }
  }

  // ---------------------------------------------------------------------------
  // Path Sanitiser
  // ---------------------------------------------------------------------------

  private function pathSanitiser() {

    // Using the original URI Format for this section, clean up path
    // by removing any dynamic twig variables and Kebabifing the path.
    $path = StringHelper::toKebabCase(trim(preg_replace('/{.*?\}/m', '', $this->section['uriFormat']),'/').'/' ?? '');

    // Variations of 'home' page paths should be ignored. As this template
    // typically exists in the templates root directory.
    if ( !in_array($path, ["home", "homepage"]) ) {
      return $path;
    }
  }

  // ---------------------------------------------------------------------------
  // Template Name Sanitiser
  // ---------------------------------------------------------------------------

  private function templateSantiser() {

    // If there is more than one entry type associated to the current section...
    if ( count($this->section['entrytypes']) > 1 ) {

      // Grab the entrytype ID from the URL. It's the last segment item.
      $entryTypeId = end($this->segments);

      // Find the index/key of the entrytypes that matches the current page
      $entryTypeIdex = array_search($entryTypeId, array_column($this->section['entrytypes'], 'id'));

      // Define the appropriate entry type data
      $entryType = $this->section['entrytypes'][$entryTypeIdex];

      // Return the entrytype handle
      return $entryType['handle'];

    } elseif ( $this->section['template'] == '_loader' || $this->section['template'] == '_loader.twig' ) {
      // If a variants of the _loader or _loader.twig was used in the section template,

      // Then check on the section type to determine a filename name default
      if ( $this->section['type'] == 'channel' || $this->section['type'] == 'structure' ) {

        // _entry for channels or structures
        return '_entry';

      } else {

        // Or index for everything else
        return 'index';

      }

    } else {
      // Lastly if all else fails. Fallback to the original template name.
      // But sanitise if by removing unwanted characters and dynamic twig variables.
      return preg_replace('/{.*?\}/m', '', preg_replace('/\\.[^.\\s]{3,4}$/', '', $this->section['template']));

    }

  }

  // ---------------------------------------------------------------------------
  // Create Template
  // ---------------------------------------------------------------------------

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
            $type = explode('\\', $field['type']);
            // $deviders = str_repeat('-', 80 - (strlen($field['name']) + 7) - (strlen($type)) - ($tabIndentationCount*4));

            // Comment line for the field name.
            // $layout .= "\n".$tabIndentation.$tabIndentation."{# ".$field['name']." ".$deviders.$type." #}\n";
            $layout .= $this->commentHeader($field['name'], end($type), 2);

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

    return [ 'filename' => $newTemplateFileName, 'path' => $newTemplateFilePath, 'uri' => $uri];

  }

  private function commentInline($heading, $suffix = null, $tabs = 1, $seperator = "-") {

    $maxLength       = 80;
    $suffix          = !empty($suffix) ? ' ['.$suffix.']' : '';
    $totalLength     = strlen($heading) + strlen($suffix) + ($tabs*$this->tabLength);
    $seperatorLength = ($maxLength - $totalLength) < 0 ? 5 : ($maxLength - $totalLength);
    $seperators      = str_repeat($seperator, $seperatorLength);
    $tabs            = str_repeat("\t", $tabs);
    $comment         = "\n".$tabs."{# ".$heading." ".$seperators.$suffix." #}\n";

    return $comment;
  }

  private function commentHeader($heading, $suffix = null, $tabs = 2, $seperator = "=") {

    $maxLength       = 80;
    $suffix          = !empty($suffix) ? '['.$suffix.']' : '';
    $totalLength     = strlen($heading) + strlen($suffix) + ($tabs*$this->tabLength);
    $seperatorLength = ($maxLength - $totalLength) < 0 ? 5 : ($maxLength - $totalLength);
    $seperators      = str_repeat($seperator, $seperatorLength);
    $tabs            = str_repeat("\t", $tabs);
    $seperators1      = str_repeat($seperator, $maxLength);
    $seperators2      = str_repeat(' ', $seperatorLength);
    $comment         = "\n".$tabs."{# ".$seperators1." #}";
    $comment        .= "\n".$tabs."{# ".$heading." ".$seperators2.$suffix." #}";
    $comment        .= "\n".$tabs."{# ".$seperators1." #}\n";

    return $comment;

  }

}

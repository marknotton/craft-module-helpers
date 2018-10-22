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

  // Exclude these tabs from being generated.
  private $tabExclusions = ['seo'];

  // Elements Tags that are valid markup and don't need to be validated.
  private $elementExceptions = ['main', 'nav', 'aside', 'header', 'footer', 'article', 'section'];

  // Matching tab names should be rendered in their own block types of they exist.
  private $blocks = ['navigation', 'header', 'main', 'content', 'aside', 'footer'];

  // Field Aliases
  // If field has a specific handle, refer to sample file by reference
  private $fieldAliases = [
    'featuredImage' => 'featured-image',
    'body'          => 'body',
    'contentBlocks' => 'content-blocks'
  ];

  // Field Aliases To Include
  // If one of the above $fieldAliases is found, clone the file and set an include
  // within the template markup. Otherwise, just include it's content.
  private $fieldAliasesToInclude = [
    'featured-image',
    'content-blocks'
  ];

  // Assign a field type to a file.
  private $fieldFiles = [
    // Craft CMS
    'craft\fields\Assets'       => 'Assets',
    'craft\fields\Matrix'       => 'Matrix',
    'craft\fields\PlainText'    => 'PlainText',
    'craft\fields\Categories'   => 'Categories',
    'craft\fields\Checkboxes'   => 'Checkboxes',
    'craft\fields\Color'        => 'Color',
    'craft\fields\Date'         => 'Date',
    'craft\fields\Dropdown'     => 'Dropdown',
    'craft\fields\Email'        => 'Email',
    'craft\fields\Lightswitch'  => 'Lightswitch',
    'craft\fields\MultiSelect'  => 'MultiSelect',
    'craft\fields\Number'       => 'Number',
    'craft\fields\Entries'      => 'Entries',
    'craft\fields\RadioButtons' => 'RadioButtons',
    'craft\fields\Table'        => 'Table',
    'craft\fields\Tags'         => 'Tags',
    'craft\fields\Url'          => 'Url',
    'craft\fields\Users'        => 'Users',
    'craft\redactor\Field'      => 'Redactor',
    // Third Party
    'modules\helpers\fields\Video' => 'Video',
    'verbb\supertable\fields\SuperTableField' => 'SuperTable',
    'supercool\tablemaker\fields\TableMakerField' => 'TableMaker'
  ];

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
      $this->section = $this->sections[array_search($sectionId, array_column($this->sections, 'id'))];

      // Set the path name and and template name.
      $path = $this->pathSanitiser();
      $templateName = $this->templateSantiser();

      // Set a timestamp to be used as a filename suffix should there be a naming conflict.
      $timestamp = $this->timestamp();

      // Get a list of all the files that exist in the templates directory.
      // This will be tested against to instruct users if they are about to
      // overwrite a existing file.
      $allFiles = json_encode(Helpers::$app->request->getFileDirectory(null)) ?? [];

      // It's unlikely the path name and template name will ever be the same.
      // If this occures, force the template name to '_entry'
      if ( $path == $templateName ) {
        $templateName = '_entry';
      }

      // Define a bunch of data that will passed into the template maker form
      $settings = [
        'path'        => $path,
        'template'    => $templateName,
        'timestamp'   => $timestamp,
        'allFiles'    => $allFiles,
      ];

      // Render the template-maker form and return the markup.
      $template = Craft::$app->view->renderTemplate("helpers/_template-maker/form", $settings);

      // Store the template markup as a string in a Javascript variable
      $templateMakerForm = "var templateMakerForm = '".str_replace(array("\n", "\r"), '', $template)."';";

      // Render the $templateMakerForm variable into the current entry type page.
      // The 'src/assets/scripts/tempalte-maker.js' will append the form to the bottom of the page.
      Craft::$app->getView()->registerJs($templateMakerForm, View::POS_HEAD);

      // $this->create($settings);
    }
  }

  public function timestamp() {
    return time();
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
      return StringHelper::toKebabCase(preg_replace('/{.*?\}/m', '', $this->section['template']));

    }

  }

  private function generator() {

    // Tab indentation count
    // $tabIndentationCount = 1;
    $tabIndentation = str_repeat("\t", 1);

    // Loop through all tabs.
    foreach ($this->getTabData() as $tab => $fields) {

      // Kebabify the key name for use as an element tag.
      $element = StringHelper::toKebabCase($tab);

      // Ignore specific tabs.
      if ( !in_array($element, $this->tabExclusions) ) {

        // Ensure the element has at lease one hyphen within the string,
        // unless the string is a known valid HTML5 singleton.
        if ( !in_array($element, $this->elementExceptions) && !strpos($element, '-') !== false ) {
          $element = $element.'-tab';
        }

        // Comment line for the tab name.
        $layout .= $this->commentHeader($tab.' Tab');

        // Tab open element.
        $layout .= "\n".$tabIndentation."<".$element.">\n";

        // Loop through all fields for this tab.
        foreach ($fields as $field) {

          $includeField = false;

          // Custom Field Types ================================================

          // If the handle matches a field alias, use a custom template instead
          if (array_key_exists($field['handle'], $this->fieldAliases)) {

            // If the field handle exists in the list field aliases array set the the associated filename
            $sampleFileName = $this->fieldAliases[$field['handle']];

            // Use the $sampleFileName to set a field type name to be used in the generated documentation.
            $fieldTypeName = array_key_exists($field['type'], $this->fieldFiles) ? $this->fieldFiles[$field['type']] : $sampleFileName;

            // Define a sample file path for the field type.
            $sampleFile = Craft::getAlias('@helpers').'/templates/_template-maker/samples/'.$sampleFileName.'.twig';

            if ( in_array($sampleFileName, $this->fieldAliasesToInclude)) {

              $includeField = true;

            }

          // Standard Field Types ==============================================

          } elseif (array_key_exists($field['type'], $this->fieldFiles)) {

            // If the field type exists in the list field files array set the the associated filename
            $sampleFileName = $this->fieldFiles[$field['type']];

            // Use the $sampleFileName to set a field type name to be used in the generated documentation.
            $fieldTypeName = $sampleFileName;

            // Define a sample file path for the field type.
            $sampleFile = Craft::getAlias('@helpers').'/templates/_template-maker/fields/'.$sampleFileName.'.twig';

          }

          // Camel Case field types to include white space
          $fieldTypeName = preg_replace('/([a-z])([A-Z])/s','$1 $2', $fieldTypeName);

          // If the file exists.
          if (file_exists($sampleFile)) {

            // Comment line for the field name.
            // $layout .= "\n".$tabIndentation.$tabIndentation."{# ".$field['name']." ".$deviders.$type." #}\n";
            $layout .= $this->commentInline($field['name'], $fieldTypeName, 2);

            if ($includeField) {

              $destination = Craft::getAlias('@templates').'/_components/'.$sampleFileName.'.twig';

              $component = Helpers::$app->request->fileexists($destination);

              if ( !$component ) {
                copy($sampleFile, $destination);
              }

              $layout .= "\n{% include '_components/".$sampleFileName."' %}\n";

            } else {

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

        }

        // Tab close element.
        $layout .= "\n".$tabIndentation."</".$element.">\n";
      }

    }

    // Write template file.
    fwrite($newTemplate, $layout);

  }

  private function commentInline($heading, $suffix = null, $tabs = 1, $seperator = "-") {

    $maxLength       = 80;
    $suffix          = !empty($suffix) ? ' ['.$suffix.']' : '';
    $totalLength     = strlen($heading) + strlen($suffix) + ($tabs*$this->tabLength);
    $seperatorLength = ($maxLength - $totalLength) < 0 ? 5 : ($maxLength - $totalLength);
    $seperators      = str_repeat($seperator, $seperatorLength);
    $tabs            = str_repeat("\t", $tabs);
    $comment         = $tabs."{# ".$heading." ".$seperators.$suffix." #}\n\n";

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
    $comment         = $tabs."{# ".$seperators1." #}";
    $comment        .= "\n".$tabs."{# ".$heading." ".$seperators2.$suffix." #}";
    $comment        .= "\n".$tabs."{# ".$seperators1." #}\n\n";

    return $comment;

  }

  // ===========================================================================
  // Create Template
  // ===========================================================================

  public function create($data) {

    // Extract the settings array into variables
    extract((array)$data);

    // Path and files names ====================================================

    $path = !empty($path) ? rtrim('/'.$path, '/') : '';
    $template = $template.$timestamp.'.twig';
    $templatePath = Craft::getAlias('@templates').$path.'/'.$template;

    // Get field & tab data ====================================================

    // Get all field type data
    $allFields = Helpers::$app->query->fields();

    // Get the entryType ID from the last parameter in the URL
    $entryTypeId = $this->segments[array_search('entrytypes', $this->segments) + 1];

    // Use the section routes ID to query all real sections by ID and grab it's entry types
    $section = (array)Craft::$app->getSections()->getSectionById($this->section['id'])->getEntryTypes();

    // With the entry type ID, search for the section for the correct entry type object.
    $entryType = $section[array_search($entryTypeId, array_column($section, 'id'))];

    // Get all tabs for this entry type
    $allTabs = $entryType->getFieldLayout()->getTabs();

    // Loop all fields within all tabs and create a clean array of useful data.
    foreach ($allTabs as $tab) {
      $fields = $tab->getFields();
      foreach ($fields as $field) {
        $tabData[$tab->name][] = [
          'name'   => $field->name   ?? false,
          'handle' => $field->handle ?? false,
          'id'     => $field->id     ?? false,
          'type'   => $allFields[array_search($field->id, array_column($allFields, 'id'))]['type'] ?? false
        ];
      }
    }

    // Create Paths ============================================================

    // If path is a directory, recursively generate the folder structure.
    if (!empty($path) && !is_dir(Craft::getAlias('@templates').'/'.$path)) {
      mkdir(Craft::getAlias('@templates').'/'.$path, 0777, true);
    }

    // Open a new file.
    $newTemplate = fopen($templatePath, 'w') or die('Cannot open file:  '.$templatePath);

    // Templating Markup =======================================================

    $markup = $this->commentHeader('Page Name', null, 0, "/");

    // Loop through all tabs.
    foreach ($tabData as $tab => $fields) {

    }

    // Write file ============================================================

    fwrite($newTemplate, $markup);

    // Data to pass back to the user ===========================================

    return [
      'path'               => ltrim($path, '/'),
      'template'           => $template,
      'templateSystemPath' => $templatePath,
      'templatePath'       => ltrim(str_replace(Craft::getAlias('@templates'), '', $templatePath), '/'),
      'newTimestamp'       => $this->timestamp()
    ];

  }

}

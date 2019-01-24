<?php

/**
 * Methods for querying Databases
 */

namespace modules\helpers\services;
use modules\helpers\Helpers;

use Craft;
use craft\base\Component;

class Queries extends Component {

  private $prefix;

  public function init() {
    $this->prefix = getenv('DB_TABLE_PREFIX') ?? '';
  }

  //////////////////////////////////////////////////////////////////////////////
  // Check database connection
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Check if the database is connected
   *
   * @return bool
   */
  public function isDatabaseConnected()  {

    if ( $cached = Helpers::$database ) {
      return $cached;
    }

    try {
      Craft::$app->db->createCommand("SELECT version FROM ".$this->prefix."info")->queryAll();
      return true;
    }
    catch(\yii\db\Exception $exception) {
      return false;
    }
  }

  //////////////////////////////////////////////////////////////////////////////
  // Check installed plugins
  //////////////////////////////////////////////////////////////////////////////

  /**
   * List all plugins if their enabled status is true into an array.
   * Each plugin that is enabled will have it's handle suffixed with the word "Enabled"
   *
   * @example If Agent plugin is installed, then agentEnabled => true
   *
   * @return array
   */
  public function getEnabledPlugins() {

    $newResults = [];

    if ( Helpers::$database ) {


			if (version_compare(Craft::$app->getInfo()->version, '3.1', '>=')) {

				$results = Craft::$app->plugins->getAllPluginInfo();

				if ($results) {
	        foreach ($results as $handle => $value) {
						if ( $value['isEnabled'] ) {
	          	$newResults[$handle.'Enabled'] = true;
						}
	        }
	      }

			} else {

	      $sql = "SELECT handle, enabled FROM ".getenv('DB_TABLE_PREFIX')."plugins" ;

	      $command = Craft::$app->db->createCommand($sql);
	      $results = $command->queryAll();

				if ($results) {
					foreach ($results as $value) {
						if ( $value['enabled'] ) {
							$newResults[$value['handle'].'Enabled'] = true;
						}
					}
				}

			}

    }

    return $newResults;
  }


  //////////////////////////////////////////////////////////////////////////////
  // Fields
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Get all the input field data
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function fields() {

    extract($this->routeOptions(func_get_args()));

    $sql = "SELECT id, name, handle, type, settings FROM ".$this->prefix."fields ";
    $sql .= "ORDER by id" ;

    $command = Craft::$app->db->createCommand($sql);
    $results = $command->queryAll();

    return $results;

  }

  //////////////////////////////////////////////////////////////////////////////
  // Routes
  //////////////////////////////////////////////////////////////////////////////

  // TODO: Add limits to queryies

  /**
   * Defines defualt options for all route methods.
   */
  private function routeOptions($arguments) {

    // Default options
    $options = [
      'limit' => 100,
      'siteId' => 1
    ];

    if (!empty($arguments) && gettype($arguments[0]) == 'array') {
      $options = array_merge($options, $arguments[0]);
    } else {
      // Loop through arguments and define settings
      for ($i=0; $i<count($arguments); $i++) {
        $setting = $arguments[$i];

        if ( is_numeric($setting) || is_null($setting) ) {
          if ( $i == 0 ) {
            $options['limit'] = $setting;
          } elseif ( $i == 1 ) {
            $options['siteId'] = $setting;
          }
        }
      }
    }

    return $options;
  }

  /**
   * Get all the entries routes
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function entryRoutes() {

    extract($this->routeOptions(func_get_args()));

    $sql = "SELECT e.id, slug, es.siteId, con.title, uri, LOWER(REPLACE(e.type, \"craft\\\\elements\\\\\", \"\")) AS elementType, sec.id AS section, sec.type AS type FROM ".$this->prefix."elements_sites es ";
    $sql .= "JOIN ".$this->prefix."elements e ON es.elementID = e.id ";
    $sql .= "JOIN ".$this->prefix."content con ON es.elementID = con.id ";
    $sql .= "JOIN ".$this->prefix."entries en ON es.elementID = en.id ";
    $sql .= "JOIN ".$this->prefix."sections sec ON en.sectionID = sec.id ";
    $sql .= "WHERE e.type = (SELECT type FROM ".$this->prefix."elements WHERE type = \"craft\\\\elements\\\\Entry\" LIMIT 1) ";
    $sql .= "AND es.enabled = 1 " ;
    $sql .= $siteId !== false && is_numeric($siteId) ? "AND es.siteId = ".$siteId." " : '';
    $sql .= "ORDER by id";

    $command = Craft::$app->db->createCommand($sql);
    $results = $command->queryAll();

    return $results;

  }

  /**
   * Get all the categories routes
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function categoryRoutes() {

    extract($this->routeOptions(func_get_args()));

    $sql = "SELECT e.id, slug, es.siteId, uri, title, LOWER(REPLACE(e.type, 'craft\\\\elements\\\\', '')) AS elementType, cg.handle AS `group` FROM ".$this->prefix."elements_sites es ";
    $sql .= "JOIN ".$this->prefix."elements e ON es.elementID = e.id ";
    $sql .= "JOIN ".$this->prefix."content con ON es.elementID = con.id ";
    $sql .= "JOIN ".$this->prefix."categories cat ON es.elementID = cat.id ";
    $sql .= "JOIN ".$this->prefix."categorygroups cg ON cat.groupID = cg.id ";
    $sql .= "WHERE (e.type = (SELECT type FROM ".$this->prefix."elements WHERE type = 'craft\\\\elements\\\\Category' LIMIT 1)) ";
    $sql .= "AND es.enabled = 1 " ;
    $sql .= $siteId !== false && is_numeric($siteId) ? "AND es.siteId = ".$siteId." " : '';
    $sql .= "ORDER by id" ;

    $command = Craft::$app->db->createCommand($sql);
    $results = $command->queryAll();

    return $results;

  }

  /**
   * Get all the entries and categories routes
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function allElementRoutes($siteId = 1) {

    $results = (object) array_merge((array) $this->entryRoutes($siteId), (array) $this->categoryRoutes($siteId));

    return $results;

  }

  /**
   * Get all the section routes rules
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @param  bool   $includeTypes [default:false] Include entry type id, name, handle, and title data too.
   * @return array
   */
  public function sectionRouteRules() {

    extract($this->routeOptions(func_get_args()));

    $sql = "SELECT sectionId AS id, uriFormat, template, siteId, name, handle, type FROM ".$this->prefix."sections_sites ss ";
    $sql .= "JOIN ".$this->prefix."sections se ON ss.sectionId = se.id ";
    $sql .= $siteId !== false && is_numeric($siteId) ? "WHERE siteId = ".$siteId." " : '';
    $sql .= "ORDER by id" ;

    $command = Craft::$app->db->createCommand($sql);
    $results = $command->queryAll();

    // TODO: Make the include of entry type data optional. $includeTypes
    // TODO: Get entry type data via mySQL command, not this faff...
    // $allSections = Craft::$app->getSections()->getAllSections();
    // foreach ($results as $key => $section) {
    //   $entrytypes = Craft::$app->getSections()->getSectionById($section['id'])->getEntryTypes();
    //   foreach ($entrytypes as &$entrytype) {
    //     $results[$key]['entrytypes'][] = [
    //       'id'     => $entrytype->id,
    //       'name'   => $entrytype->name,
    //       'handle' => $entrytype->handle,
    //       'title'  => $entrytype->hasTitleField ? $entrytype->titleLabel : false
    //     ];
    //   }
    // };

    return $results;

  }

  /**
   * Get all the category routes rules
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function categoryRouteRules() {

    extract($this->routeOptions(func_get_args()));

    $sql = "SELECT groupId AS id, uriFormat, template, siteId, name, handle FROM ".$this->prefix."categorygroups_sites ss ";
    $sql .= "JOIN ".$this->prefix."categorygroups ca ON ss.groupId = ca.id ";
    $sql .= $siteId !== false && is_numeric($siteId) ? "WHERE siteId = ".$siteId." " : '';
    $sql .= "ORDER by id" ;

    $command = Craft::$app->db->createCommand($sql);
    $results = $command->queryAll();

    return $results;

  }

	/**
	 * Get all the product type routes rules
	 * @param  int    $siteId  Site ID. Defaults to 1
	 * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
	 * @return array
	 */
	public function productRouteRules() {

		extract($this->routeOptions(func_get_args()));

		try {
			$sql = "SELECT productTypeId AS id, uriFormat, template, siteId, name, handle FROM ".$this->prefix."commerce_producttypes_sites ss ";
			$sql .= "JOIN ".$this->prefix."commerce_producttypes pi ON ss.productTypeId = pi.id ";
			$sql .= $siteId !== false && is_numeric($siteId) ? "WHERE siteId = ".$siteId." " : '';
			$sql .= "ORDER by id" ;

			$command = Craft::$app->db->createCommand($sql);
			$results = $command->queryAll();
			return $results;

		}
		catch(\yii\db\Exception $exception) {
			return [];
		}


	}

  /**
   * Get all the route rules
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
	 public function routeRules() {

     extract($this->routeOptions(func_get_args()));

 		// If Craft is version 3.1 or higher, then get the Routes options natively
 		if (version_compare(Craft::$app->getInfo()->version, '3.1', '>=')) {
 			$results = Craft::$app->getRoutes()->getDbRoutes();
 		} else {
 			// Otherwise do my refined and direct mySQL query.
 			$sql = "SELECT uriPattern, template FROM ".$this->prefix."routes ";
 			$sql .= $siteId !== false && is_numeric($siteId) ? "WHERE (ISNULL(siteId) OR siteId = ".$siteId.") " : " ";
 			$sql .= "ORDER by id";

 			$command = Craft::$app->db->createCommand($sql);
 			$results = $command->queryAll();

 			$cmsRoutes = [];

 			if (!empty($results)) {
 				foreach($results as &$value) {
 					$cmsRoutes[$value['uriPattern']] = ['template' => $value['template']];
 				}
 			};
 		}

     $results = array_merge(
       Craft::$app->getRoutes()->getConfigFileRoutes(),
       $results
     );

     return $results;

   }

  /**
   * Get all the section, category, and route rules
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function allRules() {

    extract($this->routeOptions(func_get_args()));

    // Get all of the sections
    $sections   = $this->sectionRouteRules($siteId);
    $categories = $this->categoryRouteRules($siteId);
    $rules      = $this->routeRules($siteId);
    $products   = $this->productRouteRules($siteId);

    if (!empty($sections)) {
      $results['sections'] = $sections;
    }

    if (!empty($categories)) {
      $results['categories'] = $categories;
    }

    if (!empty($rules)) {
      $results['rules'] = $rules;
    }

		if (!empty($products)) {
			$results['products'] = $products;
		}

    return $results;

  }

  /**
   * Get everything
   * @param  int    $siteId  Site ID. Defaults to 1
   * @param  int    $limit  Limit the amount of results. Default to 100. Use Null for unlimited
   * @return array
   */
  public function allRoutesAndRules() {

    extract($this->routeOptions(func_get_args()));

    // Get all of the sections
    $elements = $this->allElementRoutes($siteId);
    $rules = $this->allRules($siteId);

    if (!empty($elements)) {
      $results['elements'] = $elements;
    }

    if (!empty($rules)) {
      $results['routes'] = $rules;
    }

    return $results;

  }

}

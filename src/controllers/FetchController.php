<?php

////////////////////////////////////////////////////////////////////////////////
// Fetch Controller
////////////////////////////////////////////////////////////////////////////////

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;

class FetchController extends Controller {

  protected $allowAnonymous = ['template', 'data'];

	// ===========================================================================
	// Template Fetcher
	// ===========================================================================

  public function actionTemplate() {

		$requests = $this->requests();
		$data     = $requests['data'];
		$response = $requests['response'];

		// Check to see if $data was defined ---------------------------------------

		if ( !empty($data) ) {
			$response['data'] = $data;
			extract((array)$data);
		}

		// Fetch Template ==========================================================

    if(!empty($template)){

      try {

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

				// Error handler -------------------------------------------------------

				unset($response['success']);
        $response['error'] = true;
        $response['message'] = $e->getMessage();

      }
    }

    return $this->asJson($response);

  }

	// ===========================================================================
	// Data Fetcher
	// ===========================================================================

	public function actionData() {

		$requests    = $this->requests();
		$data        = $requests['data'];
		$response    = $requests['response'];
		$allFields   = Helpers::$app->query->fields();
		$content     = [];
		$exclusions  = ['id', 'limit', 'section'];
		$defaultData = ['title', 'id', 'slug', 'uri', 'type'];

		// Extract keys/values from $data if it isn't empty ------------------------

		if ( !empty($data) ) {
			$response['data'] = $data;

			if ( !empty($allFields) ) {

				$fieldHandles = array_column($allFields, 'handle');

				foreach (array_keys($data) as &$value) {
				  if (in_array($value, $fieldHandles)) {
						array_push($defaultData, $value);
					}
				}

			}

			extract((array)$data);
		}

		// Extract keys/values from $criteria if it isn't empty --------------------

		if ( !empty($criteria) ) {
			if ( $criteria->data ?? false ) {
				$defaultData = array_merge($defaultData, (array)$criteria->data);
			}

			if ( $criteria->criteria ?? false ) {
				extract((array)$criteria->criteria);
			} else {
				extract((array)$criteria);
			}
		}

		// Get the global settings incase it needs to be refered to ----------------

		$settings = Helpers::$app->request->getSettings();

		// Use $criteria and $data to query for results ----------------------------

		try {

			// If a section key was passed. Assume an entry has attempted to be rendered
			if (!empty($section) ) {

				if ( is_numeric($section) ) {
					$_section = Craft::$app->getSections()->getSectionById($section);
				} else if ( is_string($section) ) {
					$_section = Craft::$app->getSections()->getSectionByHandle($section);
				}

				if ( !empty($_section) ) {
					$response['section'] = [
						'id' => $_section->id,
						'name' => $_section->name,
						'handle' => $_section->handle,
						'type' => $_section->type,
					];
				}
			}

			// TODO: Surely there must be a better way to apply multiple critieras for one request

			if (!empty($id)) {

				if ( is_array($id) && count($id) > 1 ) {
					$entries = Entry::find()->id($id)->section($section ?? null)->limit($limit ?? null)->offset($offset ?? null)->all();
				} else {
					$entries = Entry::find()->id($id)->section($section ?? null)->limit($limit ?? null)->offset($offset ?? null)->one();
				}
			} else {
				$entries = Entry::find()->section($section ?? null)->limit($limit ?? null)->offset($offset ?? null)->all();
			}

			// foreach ($criteria as $key => $value) {
			//
			// 	if (!in_array($key, $exclusions)) {
			// 		$defaultData[] = $key;
			// 	}
			// }

			foreach ($entries as $entry) {

				$result = [];

				foreach ($defaultData as $crit) {

					if ($crit == 'type') {

						$result[$crit] = [
							'id' => $entry[$crit]['id'],
							'name' => $entry[$crit]['name'],
							'handle' => $entry[$crit]['handle'],
						];

					} else {
						if ( $entry[$crit]->elementType ?? false && $entry[$crit]->elementType == "craft\/elements\Asset")  {


							$properties = [];
							$properties['image'] = $entry[$crit]->one();
							$properties['imageTransform'] = $settings['thumb'];

							$markup = Craft::$app->getView()->renderTemplate('_components/image', $properties);

							// Removes whitespace between HTML tags
							if ( preg_match( '/(\s){2,}/s', $markup ) === 1 ) {
			          $markup = preg_replace( '/(\s){2,}/s', '', $markup );
			        }

							$result[$crit] = $markup;

						} else {

							$result[$crit] = $entry[$crit];

						}

					}
				}

				$content[] = $result;
			}

			// $response['criteria'] = $defaultData;
			$response['message'] = count($content).' results were found.';
			$response['entries'] = $content;
			$response['success'] = true;


		} catch(\Exception $e) {

			// Error handler -------------------------------------------------------

			unset($response['success']);
			$response['error'] = true;
			$response['message'] = $e->getMessage();

		}

		return $this->asJson($response);

	}

	// ===========================================================================
	// Parameter Requests
	// ===========================================================================

	private function requests() {

		$response = [];
		$data = false;

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
      $request = $_SERVER['HTTP_X_REQUESTED_WITH'];
      switch (strtolower($request)) {

				// AJAX data -----------------------------------------------------------

				case 'xmlhttprequest':
          $response['request'] = "ajax";
					$response['message'] = 'A template path was not defined in your AJAX "data" parameters.';

					try {
						$data = Craft::$app->getRequest()->getBodyParams();
					} catch(\Exception $e) {
						$response['error'] = true;
						$response['message'] = $e->getMessage();
					}
        break;

				// Fetch data ----------------------------------------------------------

				case 'fetch':
          $response['request'] = "fetch";
					$response['message'] = 'A template path was not defined in your Fetch "body" parameters.';

					try {
						$data = json_decode(file_get_contents('php://input'));
					} catch(\Exception $e) {
						$response['error'] = true;
						$response['message'] = $e->getMessage();
					}
        break;
      }
    } else {

			// URL data --------------------------------------------------------------

			$response['request'] = "direct";
			$response['message'] = 'A template path was not defined in the URL parameters.';

			try {
				$data = Craft::$app->getRequest()->resolve()[1];
				unset($data['p']);
			} catch(\Exception $e) {
				$response['error'] = true;
				$response['message'] = $e->getMessage();
			}
		}

		// Return the data and responces

		return ["data" => $data, "response" => $response];

	}

}

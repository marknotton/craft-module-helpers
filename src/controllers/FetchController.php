<?php

namespace modules\helpers\controllers;

use modules\helpers\Helpers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;

class FetchController extends Controller {

  protected $allowAnonymous = ['template'];

  public function actionTemplate() {

		$response = [];

		// Manage Data Parameters ==================================================

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

}

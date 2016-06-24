<?php

/**
 * Extends the page controller to allow modules to have their own request handling
 *
 * @package inpage-modules
 */
class ContentModule_PageController_Extension extends Extension
{
    private static $allowed_actions = array(
        'm'
    );
//
//	private static $url_handlers = array(
//		'm//$Module/$ModuleAction' => 'handleModuleRequest'
//	);

    /**
     * Action for the module, finds the appropriate module and passes the request handling on
     * @return mixed
     */
    public function m($request = null)
    {
        if (!$request) {
            $request = $this->owner->request;
        }

        if ($request) {

            $response = ModuleAsController::module_controller_for_request($this->owner, $request);

            if ($response) {
                return $response;
            }
        }

        return $this->owner->redirect($this->owner->Link());
    }
}

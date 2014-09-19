<?php
/**
 * Extension class for adding ContentModule functionality to pages
 *
 * @package inpage-modules
 */

class ContentModule_PageExtension extends DataExtension
{


	private static $many_many = array(
                'ContentModules' => 'ContentModule'
        );

	private static $many_many_extraFields = array(
                'ContentModules' => array(
                        'Sort' => 'Int'
                )
        );

	/**
	 * Returns the ContentModules sorted
	 *
	 * @return DataList
	 */
	public function SortedContentModules() {
                return ContentModule::get()
                        ->innerJoin('Page_ContentModules', '"ContentModule"."ID" = "Page_ContentModules"."ContentModuleID"')
                        ->where("\"Page_ContentModules\".\"PageID\" = '{$this->owner->ID}'")
                        ->sort('"Sort" ASC');
        }

        public function updateCMSFields(FieldList $fields) {
                $fields->addFieldToTab('Root.Modules', new ContentModuleField('ContentModules'));
        }

}

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

//	private static $url_handlers = array(
//		'm//$Module/$ModuleAction' => 'm'
//	);

	/**
	 * Action for the module, finds the appropriate module and passes the request handling on
	 * @return mixed
	 */
	public function m($request = null) {

		if (!$request) $request = $this->owner->request;

		if($request){
			if (($urlSegment = $request->param('ID')) && ($moduleAction = $request->param('OtherID'))) {

				$request->shift(2);
				$module = ContentModule::get()->filter('URLSegment', $urlSegment)->first();
				if ($module && $module->hasMethod($moduleAction)) {
					$response = $module->{$moduleAction}();



					if (is_subclass_of($response, 'RequestHandler') && !($response instanceof Form)) {
						return $response->handleRequest($request, new DataModel());
					}

					return $response;
				}
			}
			else if ($urlSegment = $request->param('ID')) {//default index action
				$request->shift(1);

				$module = ContentModule::get()->filter('URLSegment', $urlSegment)->first();

				if ($module && $module->hasMethod('index')) {
					$return = $module->index();

					if (is_subclass_of($return, 'RequestHandler')) {
						$return = $return->handleRequest();
					}
					//ajax request the module handles the response
					if ($this->owner->request->isAjax()) {
						return $return;
					}

					//for normal request, the module can change its state, but the request is handled by the page
					return $this->owner->index();
				}
			}

			$this->owner->redirect($this->owner->Link());
		}
		else{
			if (($urlSegment = $this->owner->request->param('ID')) && ($moduleAction = $this->owner->request->param('OtherID'))) {
				$module = ContentModule::get()->filter('URLSegment', $urlSegment)->first();

				if ($module && $module->hasMethod($moduleAction)) {
					return $module->{$moduleAction}();
				}
			}
			else if ($urlSegment = $this->owner->request->param('ID')) {//default index action
				$module = ContentModule::get()->filter('URLSegment', $urlSegment)->first();
				if ($module && $module->hasMethod('index')) {
					$return = $module->index();

					//ajax request the module handles the response
					if ($this->owner->request->isAjax()) {
						return $return;
					}

					//for normal request, the module can change its state, but the request is handled by the page
					return $this->owner->index();
				}
			}

			$this->owner->redirect($this->owner->Link());
		}
        }
}
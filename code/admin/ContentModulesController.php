<?php

/**
 * @package cms
 */
class ContentModulesController extends ContentModuleMain {
	
	static $url_segment = 'content-modules';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 30;
	static $menu_title = 'Content Modules';
	static $required_permission_codes = 'CMS_ACCESS_ContentModule';
	static $session_namespace = 'ContentModuleMain';

	public function LinkPreview() {
		return false;
	}

	/**
	 * @return String
	 */
	public function ViewState() {
		return $this->request->getVar('view');
	}

	public function isCurrentModule(DataObject $record) {
		return false;
	}

	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		return $items;

	}
}

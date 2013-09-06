<?php

/**
 * @package cms
 */
class ContentModuleSettingsController extends ContentModuleMain {

	static $url_segment = 'content-modules/settings';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 42;
	static $required_permission_codes = 'CMS_ACCESS_ContentModule';
	static $session_namespace = 'CMSMain';
		
	public function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentModuleID());
		
		return parent::getEditForm($record, ($record) ? $record->getSettingsFields() : null);
	}

}

<?php

/**
 * @package cms
 */
class ContentModuleSettingsController extends ContentModuleMain {

	private static $url_segment = 'content-modules/settings';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $url_priority = 42;
	private static $required_permission_codes = 'CMS_ACCESS_ContentModule';
	private static $session_namespace = 'CMSMain';
		
	public function getEditForm($id = null, $fields = null) {
		$record = $this->getRecord($id ? $id : $this->currentModuleID());
		
		return parent::getEditForm($record, ($record) ? $record->getSettingsFields() : null);
	}

}

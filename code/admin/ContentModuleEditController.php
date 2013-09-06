<?php

/**
 * @package cms
 */
class ContentModuleEditController extends ContentModuleMain {

	static $url_segment = 'content-modules/edit';
	static $url_rule = '/$Action/$ID/$OtherID';
	static $url_priority = 31;
	static $required_permission_codes = 'CMS_ACCESS_ContentModule';
	static $session_namespace = 'ContentModuleMain';

}

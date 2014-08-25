<?php

/**
 * @package cms
 */
class ContentModuleEditController extends ContentModuleMain {

	private static $url_segment = 'content-modules/edit';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $url_priority = 41;
	private static $required_permission_codes = 'CMS_ACCESS_ContentModule';
	private static $session_namespace = 'ContentModuleMain';

}

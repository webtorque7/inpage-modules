<?php

/**
 * Adds base language functionality to @link ContentModule
 */
class ContentModuleLanguageExtension extends DataExtension
{
	private static $db = array(
		'Locale' => 'DBLocale'
	);

	private static $has_one = array(
		'Original' => 'ContentModule'
	);

	private static $summary_fields = array(
		'Locale'
	);

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		DB::query(sprintf('UPDATE "ContentModule" SET "Locale" = \'%s\' WHERE "Locale" = \'\' OR "Locale" IS NULL', Translatable::default_locale()));
		DB::query(sprintf('UPDATE "ContentModule_Live" SET "Locale" = \'%s\' WHERE "Locale" = \'\' OR "Locale" IS NULL', Translatable::default_locale()));
		DB::query(sprintf('UPDATE "ContentModule_versions" SET "Locale" = \'%s\' WHERE "Locale" = \'\' OR "Locale" IS NULL', Translatable::default_locale()));

		DB::alteration_message('Updated ContentModule for Translatable', 'changed');
	}
}
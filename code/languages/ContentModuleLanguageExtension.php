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

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        DB::query(sprintf('UPDATE "ContentModule" SET "Locale" = \'%s\' WHERE "Locale" = \'\' OR "Locale" IS NULL', Translatable::default_locale()));
        DB::query(sprintf('UPDATE "ContentModule_Live" SET "Locale" = \'%s\' WHERE "Locale" = \'\' OR "Locale" IS NULL', Translatable::default_locale()));
        DB::query(sprintf('UPDATE "ContentModule_versions" SET "Locale" = \'%s\' WHERE "Locale" = \'\' OR "Locale" IS NULL', Translatable::default_locale()));

        DB::alteration_message('Updated ContentModule for Translatable', 'changed');
    }

    public function augmentSQL(SQLQuery &$query, DataQuery $dataQuery = null)
    {
        if ($this->owner->ID && !empty($this->owner->Locale)) {
            $locale = $this->owner->Locale;
        } else {
            $locale = Translatable::get_current_locale();
        }

        if ($locale && Translatable::locale_filter_enabled()) {
            $qry = sprintf('"ContentModule"."Locale" = \'%s\'', Convert::raw2sql($locale));
            $query->addWhere($qry);
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->owner->Locale) {
            $this->owner->Locale = Translatable::get_current_locale();
        }
    }
}

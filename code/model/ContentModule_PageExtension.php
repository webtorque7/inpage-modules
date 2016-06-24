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
    public function SortedContentModules()
    {
        return ContentModule::get()
            ->innerJoin('Page_ContentModules', '"ContentModule"."ID" = "Page_ContentModules"."ContentModuleID"')
            ->where("\"Page_ContentModules\".\"PageID\" = '{$this->owner->ID}'")
            ->sort('"Sort" ASC');
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Modules', new ContentModuleField('ContentModules'));
    }
}
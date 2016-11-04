<?php

/**
 * Extension class for adding ContentModule functionality to pages
 *
 * @package inpage-modules
 */
class ContentModule_PageExtension extends DataExtension
{

    private static $db = array(
        'UseDefaultHeader' => 'Enum("Inherit, Yes, No", "Inherit")',
        'UseDefaultFooter' => 'Enum("Inherit, Yes, No", "Inherit")'
    );

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        //default them if not set (for older records before these were added)
        if (!$this->owner->UseDefaultHeader) $this->owner->UseDefaultHeader = 'Inherit';
        if (!$this->owner->UseDefaultFooter) $this->owner->UseDefaultFooter = 'Inherit';
    }

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

    public function updateSettingsFields(FieldList $fields)
    {
        if (Config::inst()->get('ContentModule', 'enable_global_modules')) {
            $fields->addFieldsToTab('Root.Settings', array(
                DropdownField::create('UseDefaultHeader', 'Use default header?', $this->owner->obj('UseDefaultHeader')->enumValues()),
                DropdownField::create('UseDefaultFooter', 'Use default footer?', $this->owner->obj('UseDefaultFooter')->enumValues())
            ));
        }
    }

    /**
     * Looks up UseDefaultHeader, if inherit looks up parents, defaults to true
     *
     * @return bool
     */
    public function UseGlobalHeader()
    {
        if ($this->owner->UseDefaultHeader === 'Inherit' && $this->owner->ParentID) {
            return $this->owner->Parent()->UseGlobalHeader();
        }

        //defaults to true if nothing set
        return $this->owner->UseDefaultHeader === 'No' ? false : true;
    }

    /**
     * Looks up UseDefaultFooter, if inherit looks up parents, defaults to true
     *
     * @return bool
     */
    public function UseGlobalFooter()
    {
        if ($this->owner->UseDefaultFooter === 'Inherit' && $this->owner->ParentID) {
            return $this->owner->Parent()->UseGlobalFooter();
        }

        //defaults to true if nothing set
        return $this->owner->UseDefaultFooter === 'No' ? false : true;
    }
}
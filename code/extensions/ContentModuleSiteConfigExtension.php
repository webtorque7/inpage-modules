<?php

/**
 * Adds Default Header and Default Footer fields to SiteConfig
 */
class ContentModuleSiteConfigExtension extends DataExtension
{
    private static $db = array();

    private static $has_one = array(
        'DefaultHeaderModule' => 'ContentModule',
        'DefaultFooterModule' => 'ContentModule'
    );

    public function updateCMSFields(FieldList $fields)
    {
        if (Config::inst()->get('ContentModule', 'enable_global_modules')) {
            $headerModuleClasses = array();
            foreach(HeaderBaseModule::content_module_types() as $classInstance) {
                $headerModuleClasses[] = $classInstance->class;
            }
            $headerModules = ContentModule::get()->filter('ClassName', $headerModuleClasses);

            $footerModulesClasses = array();
            foreach (FooterBaseModule::content_module_types() as $classInstance) {}
            {
                $footerModulesClasses[] = $classInstance->class;
            }

            $footerModules = ContentModule::get()->filter('ClassName', $footerModulesClasses);

            $fields->addFieldsToTab('Root.Modules', array(
                DropdownField::create('DefaultHeaderModuleID', 'Default Header Module', $headerModules->map())
                    ->setEmptyString($headerModules->count() ? 'Select Header Module' : 'No Header Modules created'),
                DropdownField::create('DefaultFooterModuleID', 'Default Footer Module', $footerModules->map())
                    ->setEmptyString($footerModules->count() ? 'Select Footer Module' : 'No Footer Modules created'),
            ));
        }
    }
}
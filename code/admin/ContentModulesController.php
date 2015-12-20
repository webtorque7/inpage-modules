<?php

/**
 * @package cms
 */
class ContentModulesController extends ContentModuleMain
{

    private static $url_segment = 'content-modules';
    private static $url_rule = '/$Action/$ID/$OtherID';
    private static $url_priority = 40;
    private static $menu_title = 'Content Modules';
    private static $required_permission_codes = 'CMS_ACCESS_ContentModule';
    private static $session_namespace = 'ContentModuleMain';

    public function LinkPreview()
    {
        return false;
    }

    /**
     * @return String
     */
    public function ViewState()
    {
        return $this->request->getVar('view');
    }

    public function isCurrentModule(DataObject $record)
    {
        return false;
    }

    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);

        return $items;
    }
}

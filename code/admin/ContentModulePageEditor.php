<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 5/07/2016
 * Time: 3:26 PM
 */
class ContentModulePageEditor extends LeftAndMain implements PermissionProvider
{
    private static $url_segment = 'content-modules/module-page-editor';

    private static $menu_title = 'Page Editor';

    private static $url_priority = 41;

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $menu_priority = -100;

    private static $tree_class = 'SiteTree';

    private static $allowed_actions = array(
        'edit'
    );


    public function init() {
        parent::init();

        Requirements::css(INPAGE_MODULES_DIR . '/css/ContentModulePageEditor.css');
        Requirements::combine_files('ContentModulePageEditor.js', [
            INPAGE_MODULES_DIR . '/javascript/ContentModulePageEditor.js',
            INPAGE_MODULES_DIR . '/javascript/ContentModulePageEditor.ToolBox.js',
            INPAGE_MODULES_DIR . '/javascript/ContentModulePageEditor.Form.js',
            INPAGE_MODULES_DIR . '/javascript/ContentModulePageEditor.ModuleManager.js'
        ]);

        //todo, get fontawesome css
        Requirements::javascript('https://use.fontawesome.com/99eadabf1d.js');

//        Requirements::css(ADMINHELP_DIR . '/css/AdminHelp.css');
    }

    public function CurrentPage($id = null) {
        return SiteTree::get()->byID($id ? $id : $this->getRequest()->param('ID'));
    }

    public function getEditForm($id = null, $fields = null)
    {
        if (!$id) $id = $this->getRequest()->param('ID');

        $fields = FieldList::create();
        $actions = FieldList::create();

        $fields->push(HiddenField::create('ID')->setValue($id));
        $navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator());
        $navField->setAllowHTML(true);
        $fields->push($navField);

        return Form::create($this, 'EditForm', $fields, $actions)->addExtraClass('cms-previewable');
    }

    public function getSilverStripeNavigator()
    {
        $page = $this->currentPage();
        if($page) {
            $navigator = new ContentModulePageEditorSilverStripeNavigator($page);
            return $navigator->renderWith($this->getTemplatesWithSuffix('_SilverStripeNavigator'));
        } else {
            return false;
        }
    }

    public function providePermissions()
    {
        return array(
            "CMS_ACCESS_ContentModulePageEditor" => array(
                'name' => _t('ContentModulePageEditor.ACCESS', "Access to '{title}' section", array('title' => 'Content Module Page Editor')),
                'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(
                    'ContentModulePageEditor.ACCESS_HELP',
                    'Allow using the page editor for modules.'
                ),
                'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
            )
        );
    }
}
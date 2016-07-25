<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 5/07/2016
 * Time: 3:26 PM
 */
class VisualEditor extends LeftAndMain implements PermissionProvider
{
    private static $url_segment = 'content-modules/visual-editor';

    private static $menu_title = 'Visual Editor';

    private static $url_priority = 41;

    private static $url_rule = '/$Action/$ID/$OtherID';

    private static $menu_priority = -100;

    private static $tree_class = 'SiteTree';

    private static $session_namespace = 'VisualEditor';

    private static $allowed_actions = array(
        'edit',
        'EditPageForm',
        'EditSettingsForm',
        'page',

        'publishPage',
        'publishSettings',

        'savePage',
        'saveSettings',
        'settings',
        'SiteTreeForm'
    );


    public function init()
    {
        parent::init();

        Requirements::css(INPAGE_MODULES_DIR . '/css/VisualEditor.css');
        Requirements::combine_files('VisualEditor.js', [
            INPAGE_MODULES_DIR . '/javascript/VisualEditor.js',
            INPAGE_MODULES_DIR . '/javascript/VisualEditor.Preview.js',
            INPAGE_MODULES_DIR . '/javascript/VisualEditor.ToolBox.js',
            INPAGE_MODULES_DIR . '/javascript/VisualEditor.Form.js',
            INPAGE_MODULES_DIR . '/javascript/VisualEditor.ModuleManager.js'
        ]);

        //todo, get fontawesome css
        Requirements::javascript('https://use.fontawesome.com/99eadabf1d.js');

        Versioned::reading_stage("Stage");

        //lets see if this works - we want to include js from CMSMain
        singleton('CMSMain')->init();
    }

    /**
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     */
    public function edit($request)
    {
        if ($id = $request->param('ID')) {
            $this->setCurrentPageID($id);
        }

        return $this->getResponseNegotiator()->respond($request);
    }

    private $_currentPage = null;

    public function getCurrentPage($id = null)
    {
        return $this->currentPage();
    }

    public function getEditForm($id = null, $fields = null)
    {
        if (!$id) {
            $id = $this->getRequest()->param('ID');
        }

        $fields = FieldList::create();
        $actions = FieldList::create();

        $fields->push(HiddenField::create('ID')->setValue($id));

        return Form::create($this, 'EditForm', $fields, $actions);
    }

    public function EditPageForm()
    {
        return $this->getEditPageForm();
    }

    public function getEditPageForm($id = null, $fields = null)
    {
        if (!$id) {
            $id = $this->getRequest()->requestVar('ID');
        }

        $page = $this->getCurrentPage($id);

        //grab tab Root.Main from page
        $mainFields = $page->getCMSFields()->fieldByName('Root.Main');

        //remove visual editor link if it's there
        if ($mainFields->fieldByName('VisualEditorLink')) {
            $mainFields->removeByName('VisualEditorLink');
        }

        $fields = FieldList::create(
            TabSet::create('Root',
                Tab::create('Main',
                    $mainFields
                )
            )
        );

        $actions = FieldList::create(
            FormAction::create('savePage', 'Save')
                ->addExtraClass('save')->setAttribute('data-icon', 'disk')
                ->setUseButtonTag(true),
            FormAction::create('publishPage', 'Publish')
                ->addExtraClass('publish')
                ->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true)
        );

        //add in fields for stages etc from CMSMain - hopefully gets url segment working
        $deletedFromStage = $page->getIsDeletedFromStage();
        $deleteFromLive = !$page->getExistsOnLive();

        $fields->push($idField = new HiddenField("ID", false, $id));
        // Necessary for different subsites
        $fields->push($liveLinkField = new HiddenField("AbsoluteLink", false, $page->AbsoluteLink()));
        $fields->push($liveLinkField = new HiddenField("LiveLink"));
        $fields->push($stageLinkField = new HiddenField("StageLink"));
        $fields->push(new HiddenField("TreeTitle", false, $page->TreeTitle));

        if ($page->ID && is_numeric($page->ID)) {
            $liveLink = $page->getAbsoluteLiveLink();
            if ($liveLink) {
                $liveLinkField->setValue($liveLink);
            }
            if (!$deletedFromStage) {
                $stageLink = Controller::join_links($page->AbsoluteLink(), '?stage=Stage');
                if ($stageLink) {
                    $stageLinkField->setValue($stageLink);
                }
            }
        }

        $form = Form::create($this, 'EditPageForm', $fields, $actions);

        $form
            ->loadDataFrom($page)
            ->setTemplate('VisualEditor_EditForm')
            ->addExtraClass('cms-edit-form cms-content');

        return $form;
    }

    public function savePage($data, $form)
    {
        $record = $this->CurrentPage($data['ID']);

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }
        if (!$record || !$record->ID) {
            throw new SS_HTTPResponse_Exception("Bad record ID #{$data['ID']}", 404);
        }

        $form->saveInto($record);
        $record->write();

        return ContentModuleUtilities::json_response(array(
            'Status' => 1,
            'Message' => 'Page ' . $record->Title . ' saved',
            'Content' => $this->page()->forTemplate()
        ));

    }

    public function publishPage($data, $form)
    {
        $record = $this->CurrentPage($data['ID']);

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }
        if (!$record || !$record->ID) {
            throw new SS_HTTPResponse_Exception("Bad record ID #{$data['ID']}", 404);
        }

        $form->saveInto($record);
        $record->write();
        $record->doPublish();

        return ContentModuleUtilities::json_response(array(
            'Status' => 1,
            'Message' => 'Page ' . $record->Title . ' published',
            'Content' => $this->page()->forTemplate()
        ));
    }

    public function page()
    {
        if (!$this->CurrentPage()->canEdit()) {
            return Security::permissionFailure($this);
        }

        return $this->getEditPageForm()->forTemplate();
    }

    public function EditSettingsForm()
    {
        return $this->getEditSettingsForm();
    }

    public function getEditSettingsForm($id = null, $fields = null)
    {
        if (!$id) {
            $id = $this->getRequest()->requestVar('ID');
        }

        $page = $this->getCurrentPage($id);

        $fields = FieldList::create(
            TabSet::create('Root', Tab::create('Settings',
                $page->getSettingsFields()->fieldByName('Root.Settings')
            ))
        );

        $actions = FieldList::create(
            FormAction::create('saveSettings', 'Save')
                ->addExtraClass('save')->setAttribute('data-icon', 'disk')
                ->setUseButtonTag(true),
            FormAction::create('publishSettings', 'Publish')
                ->addExtraClass('publish')
                ->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true)
        );

        $fields->push(HiddenField::create('ID')->setValue($id));

        $form = Form::create($this, 'EditSettingsForm', $fields, $actions);

        $form
            ->loadDataFrom($page)
            ->setTemplate('VisualEditor_EditForm')
            ->addExtraClass('cms-edit-form cms-content');

        return $form;
    }

    public function saveSettings($data, $form)
    {
        $record = $this->CurrentPage($data['ID']);

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }
        if (!$record || !$record->ID) {
            throw new SS_HTTPResponse_Exception("Bad record ID #{$data['ID']}", 404);
        }

        $form->saveInto($record);
        $record->write();

        return ContentModuleUtilities::json_response(array(
            'Status' => 1,
            'Message' => 'Page ' . $record->Title . ' saved',
            'Content' => $this->settings()->forTemplate()
        ));

    }

    public function publishSettings($data, $form)
    {
        $record = $this->CurrentPage($data['ID']);

        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }
        if (!$record || !$record->ID) {
            throw new SS_HTTPResponse_Exception("Bad record ID #{$data['ID']}", 404);
        }

        $form->saveInto($record);
        $record->write();
        $record->doPublish();

        return ContentModuleUtilities::json_response(array(
            'Status' => 1,
            'Message' => 'Page ' . $record->Title . ' published',
            'Content' => $this->settings()->forTemplate()
        ));

    }

    public function settings()
    {
        if (!$this->CurrentPage()->canEdit()) {
            return Security::permissionFailure($this);
        }

        return $this->getEditSettingsForm()->forTemplate();
    }

    public function SiteTreeForm()
    {
        $fields = FieldList::create(
            TreeDropdownField::create('SiteTreeID', '', 'SiteTree')->setValue($this->currentPageID())
        );

        $actions = FieldList::create();

        return Form::create($this, 'SiteTreeForm', $fields, $actions)->addExtraClass('site-tree-form');
    }


    public function PageEditLink()
    {
        return Controller::join_links(singleton('CMSPageEditController')->Link('show'), $this->CurrentPage()->ID);
    }

    /**
     * Caution: Volatile API.
     *
     * @return PjaxResponseNegotiator
     */
    public function getResponseNegotiator()
    {
        if (!$this->responseNegotiator) {
            $controller = $this;
            $this->responseNegotiator = new PjaxResponseNegotiator(
                array(
                    'CurrentForm' => function () use (&$controller) {
                        return $controller->getEditForm()->forTemplate();
                    },
                    'Content' => function () use (&$controller) {
                        return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
                    },
                    'default' => function () use (&$controller) {
                        return $controller->renderWith($controller->getViewer('edit'));
                    }
                ),
                $this->getResponse()
            );
        }
        return $this->responseNegotiator;
    }

    public function getSilverStripeNavigator()
    {
        $page = $this->currentPage();
        if ($page) {
            $navigator = new VisualEditorSilverStripeNavigator($page);
            return $navigator->renderWith($this->getTemplatesWithSuffix('_SilverStripeNavigator'));
        } else {
            return false;
        }
    }

    public function providePermissions()
    {
        return array(
            "CMS_ACCESS_VisualEditor" => array(
                'name' => _t('VisualEditor.ACCESS', "Access to '{title}' section",
                    array('title' => 'Content Module Page Editor')),
                'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
                'help' => _t(
                    'VisualEditor.ACCESS_HELP',
                    'Allow using the page editor for modules.'
                ),
                'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
            )
        );
    }

    public function Link($action = null, $id = null, $otherID = null, $otherParam = null)
    {
        // Handle missing url_segments
        if($this->config()->url_segment) {
            $segment = $this->config()->get('url_segment', Config::FIRST_SET);
        } else {
            $segment = $this->class;
        };

        $link = Controller::join_links(
            $this->stat('url_base', true),
            $segment,
            '/', // trailing slash needed if $action is null!
            "$action",
            $id,
            $otherID,
            $otherParam
        );
        $this->extend('updateLink', $link);
        return $link;
    }
}
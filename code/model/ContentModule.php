<?php

/**
 * Base class for modules
 *
 * @package inpage-modules
 */
class ContentModule extends DataObject implements PermissionProvider
{

    /**
     * Used in CMS for setting form on variables which require it
     * @var Form
     */
    public $form;

    protected $_currentModuleField, $test, $_cache_statusFlags;

    protected static $has_url = false;

    private static $db = array(
        'Title' => 'Varchar',
        'Reuseable' => 'Boolean',
        'URLSegment' => 'Varchar'
    );

    private static $defaults = array(
        'WorkflowDefinitionID' => 1
    );

    private static $summary_fields = array(
        'Title',
        'Type'
    );

    private static $casting = array(
        'Type' => 'Varchar'
    );

    private static $extensions = array(
        "Versioned('Stage', 'Live')"
    );

    private static $exclude_modules = array();

    /**
     * Sets tabs to all the same height
     *
     * @var bool
     * @config
     */
    private static $fix_tab_heights = false;

    public function getCMSFields()
    {
        $fields = new FieldList(
            $rootTab = new TabSet("Root",
                $tabMain = new Tab('Main',
                    new TextField('Title', 'Module Name'),
                    new CheckboxField('Reuseable', 'Save to library?')
                )
            )
        );

        if ($this->stat('has_url')) {
            $fields->addFieldToTab('Root.Main', new TextField('URLSegment', 'Unique URL'), 'Reuseable');
        }

        $fields->removeByName('Version');
        //$fields->removeByName('URLSegment');
        $fields->push(new HiddenField('Sort'));

        //used for saving in admin
        $fields->push(new HiddenField('ClassName'));

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }

    /**
     * @param bool $renameActions if set renames actions with record id appended to prevent naming conflicts
     * @return an|FieldList
     */
    public function getCMSActions($renameActions = false)
    {
        $minorActions = CompositeField::create()->setTag('fieldset')->setName('MajorActions')->addExtraClass('ss-ui-buttonset noborder');
        $actions = new FieldList($minorActions);

        // "readonly"/viewing version that isn't the current version of the record
        $stageOrLiveRecord = Versioned::get_one_by_stage($this->class, Versioned::current_stage(),
            sprintf('"ContentModule"."ID" = %d', $this->ID));
        if ($stageOrLiveRecord && $stageOrLiveRecord->Version != $this->Version) {
            //$minorActions->push(FormAction::create('email', _t('CMSMain.EMAIL', 'Email')));
            //$minorActions->push(FormAction::create('rollback', _t('CMSMain.ROLLBACK', 'Roll back to this version')));

            // getCMSActions() can be extended with updateCMSActions() on a extension
            $this->extend('updateCMSActions', $actions);

            return $actions;
        }

        if (($cModField = ContentModuleField::curr()) && $this->exists()) {
            // "unlink"
            $minorActions->push(
                FormAction::create($renameActions ? 'unlink_' . $this->ID : 'unlink',
                    _t('ContentModule.BUTTONUNLINK', 'Unlink'))
                    ->setDescription(_t('ContentModule.BUTTONUNLINKDESC', 'Unlink this module from the current page'))
                    ->addExtraClass('ss-ui-action-destructive unlink')->setAttribute('data-icon', 'unlink')
            );
        }

        if ($this->isPublished() && $this->canPublish() && !$this->IsDeletedFromStage && $this->canDeleteFromLive()) {
            // "unpublish"
            $minorActions->push(
                FormAction::create($renameActions ? 'unpublish_' . $this->ID : 'unpublish',
                    _t('ContentModule.BUTTONUNPUBLISH', 'Unpublish'))
                    ->setDescription(_t('ContentModule.BUTTONUNPUBLISHDESC',
                        'Remove this module from the published site'))
                    ->addExtraClass('ss-ui-action-destructive unpublish')
                    ->setAttribute('data-icon', 'unpublish')
                    ->setUseButtonTag(true)
            );
        }

        if ($this->stagesDiffer('Stage', 'Live') && !$this->IsDeletedFromStage) {
            if ($this->isPublished() && $this->canEdit()) {
                // "rollback"
                $minorActions->push(
                    FormAction::create($renameActions ? 'rollback_' . $this->ID : 'rollback',
                        _t('ContentModule.BUTTONCANCELDRAFT', 'Cancel draft changes'))
                        ->setDescription(_t('ContentModule.BUTTONCANCELDRAFTDESC',
                            'Delete your draft and revert to the currently published module'))
                        ->addExtraClass('rollback')
                );
            }
        }

        if ($this->canEdit()) {
            if ($this->IsDeletedFromStage) {
                if ($this->ExistsOnLive) {
                    // "restore"
                    $minorActions->push(
                        FormAction::create($renameActions ? 'revert_' . $this->ID : 'revert',
                            _t('CMSMain.RESTORE', 'Restore'))
                            ->addExtraClass('revert ss-ui-action-destructive')
                    );
                    if ($this->canDelete() && $this->canDeleteFromLive()) {
                        // "delete from live"
                        $minorActions->push(
                            FormAction::create($renameActions ? 'deletefromlive_' . $this->ID : 'deletefromlive',
                                _t('CMSMain.DELETEFP', 'Delete'))
                                ->addExtraClass('deletefromlive ss-ui-action-destructive')
                                ->setAttribute('data-icon', 'decline')
                                ->setUseButtonTag(true)
                        );
                    }
                } else {
                    // "restore"
                    $minorActions->push(
                        FormAction::create($renameActions ? 'restore_' . $this->ID : 'restore',
                            _t('CMSMain.RESTORE', 'Restore'))
                            ->setAttribute('data-icon', 'decline')
                            ->addExtraClass('restore')
                            ->setUseButtonTag(true)
                    );
                }
            } else {
                if ($this->canDelete()) {
                    // "delete"
                    $minorActions->push(
                        FormAction::create($renameActions ? 'delete_' . $this->ID : 'delete',
                            _t('ContentModule.DELETE', 'Delete'))->addExtraClass('delete ss-ui-action-destructive')
                            ->setAttribute('data-icon', 'decline')
                            ->setUseButtonTag(true)
                    );
                }

                // "save"
                $minorActions->push(
                    FormAction::create($renameActions ? 'save_' . $this->ID : 'save',
                        _t('CMSMain.SAVEDRAFT', 'Save Draft'))
                        ->addExtraClass('save')->setAttribute('data-icon', 'disk')
                        ->setUseButtonTag(true)
                );
            }
        }

        if ($this->canPublish() && !$this->IsDeletedFromStage) {
            // "publish"
            $actions->push(
                FormAction::create($renameActions ? 'publish_' . $this->ID : 'publish',
                    _t('ContentModule.BUTTONSAVEPUBLISH', 'Save & Publish'))
                    ->addExtraClass('publish')
                    ->setAttribute('data-icon', 'accept')
                    ->setUseButtonTag(true)
            );
        }

        // getCMSActions() can be extended with updateCMSActions() on a extension
        $this->extend('updateCMSActions', $actions);

        return $actions;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        //default title
        if (!$this->Title) {
            $class = get_class($this);
            $objects = DataObject::get($class);
            $this->Title = singleton($class)->i18n_singular_name() . ' ' . ($objects->count() + 1);
        }

        if (!$this->URLSegment) {
            $this->URLSegment = URLSegmentFilter::create()->filter($this->Title);
        } elseif ($this->isChanged('URLSegment')) {
            $this->URLSegment = URLSegmentFilter::create()->filter($this->URLSegment);
        }

        if ($this->isChanged('URLSegment') && ($original = $this->URLSegment)) {
            //make sure it is unique
            $safe = false;
            $counter = 1;
            while (!$safe) {
                $counter++;
                if (
                ContentModule::get()
                    ->filter(array('URLSegment' => $this->URLSegment))
                    ->exclude('ID', $this->ID)->first()
                ) {
                    $this->URLSegment = $original . '-' . $counter;
                } else {
                    $safe = true;
                }
            }
        }
    }


    /**
     * Makes a copy of the ContentModule
     * @return $this
     */
    public function copy()
    {
        $copy = clone $this;
        $copy->ID = 0;
        $copy->write();

        return $copy;
    }

    /**
     * Gets the list of fields for editing the module, and modifies the Name
     * to make it suitable to work with ContentModuleField
     * @return FieldList
     */
    public function EditFields($values = null, $rename = true)
    {
        $fields = $this->getCMSFields();

        $this->renameFields($fields, $values, $rename);

        $this->extend('updateEditFields', $fields);

        return $fields;
    }

    public function renameFields($fields, $values, $rename)
    {
        if ($fields) {
            foreach ($fields as $field) {

                if (!is_a($field, 'CompositeField')) {
                    /**
                     * @var $field FormField
                     */
                    $name = $field->getName();

                    //rename the field to tie it to the module
                    $newFieldName = "ContentModule[{$this->ID}][{$name}]";

                    //we don't rename when using for updating record
                    if ($rename) {
                        if ($field->hasMethod('setContentModuleNames')) {
                            $field->setContentModuleNames($name, $newFieldName);
                        }
                        $field->setName($newFieldName);
                    }

                    $value = null;
                    if (isset($this->{$name}) || $this->hasMethod('get' . ucfirst($name))) {
                        $value = $this->{$name};
                    } elseif ($this->hasMethod($name)) {
                        $value = $this->{$name}();
                    }

                    $value = (!empty($values) && isset($values[$name])) ? $values[$name] : $value;

                    if (is_a($field, 'CheckboxField')) {
                        if (!empty($values) && !isset($values[$name])) {
                            $value = false;
                        }
                    }

                    switch ($field->class) {
                        case 'UploadField':
                        case 'ContentModuleUploadField':
                            if (!empty($values) && !empty($values[$name])) {
                                $field->setValue($values[$name], $this);
                            } elseif (!empty($values)) {
                                $field->setValue(null);
                            } else {
                                $field->setValue(null, $this);
                            }
                            break;
                        default:
                            $field->setValue($value, $this);
                            break;
                    }

                    if ($field->hasMethod('setRecord')) {
                        $field->setRecord($this);
                    }
                }
                //composite field
                if ($field->hasMethod('getChildren')) {
                    $this->renameFields($field->getChildren(), $values, $rename);
                }

                if ($this->form) {
                    $field->setForm($this->form);
                }

                if (($contentModuleField = $this->getCurrentModuleField()) && $contentModuleField->getForm()) {
                    $field->setForm($contentModuleField->getForm());
                }
            }
        }
    }

    public function EditActions()
    {
        $actions = $this->getCMSActions(true);

        foreach ($actions as $field) {
            if ($this->form) {
                $field->setForm($this->form);
            }

            if (($contentModuleField = $this->getCurrentModuleField()) && $contentModuleField->getForm()) {
                $field->setForm($contentModuleField->getForm());
            }
        }

        return $actions;
    }

    public function EditForm($relationship = '')
    {
        $classes = ClassInfo::ancestry($this->class);
        $formTemplates = array();

        foreach ($classes as $class) {
            $formTemplates[] = $class . 'EditForm';
        }

        return $this->customise(['Relationship' => $relationship])->renderWith(array_reverse($formTemplates));
    }

    public function forTemplate()
    {
        $controller = ModuleAsController::controller_for($this);

        //backwards compatibility support for Modules directly handling actions
        if ($controller instanceof ModuleController) {
            $controller->setRequest(Controller::curr()->getRequest());
            $controller->setFailover($this);
        }

        $html = $controller->renderWith($controller->getViewer(''));

        //check if we are in editor mode, if so inject html to handle modules
        if ($this->getIsEditorMode()) {
            $html = $this->injectVisualEditor($html);
        }

        return $html;
    }

    /**
     * Gets modules based on called class, excludes base class (calling class),
     * any which have been specifically excluded, and any implementing HiddenClass
     *
     * @return array
     */
    public static function content_module_types()
    {
        $base = get_called_class();
        $types = ClassInfo::subclassesFor($base);

        $aTypes = array();

        if ($types) {
            foreach ($types as $type) {
                $instance = singleton($type);
                if (
                    $type != $base &&
                    !in_array($type, singleton($base)->stat('exclude_modules')) &&
                    !($instance instanceof HiddenClass)
                ) {
                    $aTypes[$instance->i18n_singular_name()] = $instance;
                }
            }
        }

        ksort($aTypes);

        return array_values($aTypes);
    }

    /**
     * Check if this content module has been published.
     *
     * @return boolean True if this page has been published.
     */
    public function isPublished()
    {
        if ($this->isNew()) {
            return false;
        }

        return (DB::query("SELECT \"ID\" FROM \"ContentModule_Live\" WHERE \"ID\" = $this->ID")->value())
            ? true
            : false;
    }

    /**
     * Check if this content module is new - that is, if it has yet to have been written
     * to the database.
     *
     * @return boolean True if this page is new.
     */
    public function isNew()
    {
        /**
         * This check was a problem for a self-hosted site, and may indicate a
         * bug in the interpreter on their server, or a bug here
         * Changing the condition from empty($this->ID) to
         * !$this->ID && !$this->record['ID'] fixed this.
         */
        if (empty($this->ID)) {
            return true;
        }

        if (is_numeric($this->ID)) {
            return false;
        }

        return stripos($this->ID, 'new') === 0;
    }

    /**
     * Compares current draft with live version,
     * and returns TRUE if no draft version of this page exists,
     * but the page is still published (after triggering "Delete from draft site" in the CMS).
     *
     * @return boolean
     */
    public function getIsDeletedFromStage()
    {
        if (!$this->ID) {
            return true;
        }
        if ($this->isNew()) {
            return false;
        }

        $stageVersion = Versioned::get_versionnumber_by_stage('ContentModule', 'Stage', $this->ID);

        // Return true for both completely deleted pages and for pages just deleted from stage.
        return !($stageVersion);
    }

    /**
     * Return true if this page exists on the live site
     */
    public function getExistsOnLive()
    {
        return (bool)Versioned::get_versionnumber_by_stage('ContentModule', 'Live', $this->ID);
    }

    /**
     * Compares current draft with live version,
     * and returns TRUE if these versions differ,
     * meaning there have been unpublished changes to the draft site.
     *
     * @return boolean
     */
    public function getIsModifiedOnStage()
    {
        // new unsaved pages could be never be published
        if ($this->isNew()) {
            return false;
        }

        $stageVersion = Versioned::get_versionnumber_by_stage('ContentModule', 'Stage', $this->ID);
        $liveVersion = Versioned::get_versionnumber_by_stage('ContentModule', 'Live', $this->ID);

        return ($stageVersion && $stageVersion != $liveVersion);
    }

    /**
     * Compares current draft with live version,
     * and returns true if no live version exists,
     * meaning the page was never published.
     *
     * @return boolean
     */
    public function getIsAddedToStage()
    {
        // new unsaved pages could be never be published
        if ($this->isNew()) {
            return false;
        }

        $stageVersion = Versioned::get_versionnumber_by_stage('ContentModule', 'Stage', $this->ID);
        $liveVersion = Versioned::get_versionnumber_by_stage('ContentModule', 'Live', $this->ID);

        return ($stageVersion && !$liveVersion);
    }

    /**
     * This function should return true if the current user can view this
     * page. It can be overloaded to customise the security model for an
     * application.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canView() on any extension returns FALSE
     * - "CanViewType" directive is set to "Inherit" and any parent page return false for canView()
     * - "CanViewType" directive is set to "LoggedInUsers" and no user is logged in
     * - "CanViewType" directive is set to "OnlyTheseUsers" and user is not in the given groups
     *
     * @uses DataExtension->canView()
     * @uses ViewerGroups()
     *
     * @return boolean True if the current user can view this page.
     */
    public function canView($member = null)
    {
        if (!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
            $member = Member::currentUserID();
        }

        // admin override
        if ($member && Permission::checkMember($member, array("ADMIN", "CONTENT_MODULE_VIEW"))) {
            return true;
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan('canView', $member);
        if ($extended !== null) {
            return $extended;
        }

        return false;
    }

    /**
     * Determines permissions for a specific stage (see {@link Versioned}).
     * Usually the stage is read from {@link Versioned::current_stage()}.
     * Falls back to {@link canView}.
     *
     * @todo Implement in CMS UI.
     *
     * @param String $stage
     * @param Member $member
     * @return boolean
     */
    public function canViewStage($stage, $member = null)
    {
        if (!$member) {
            $member = Member::currentUser();
        }

        return $this->canView($member);
    }

    /**
     * This function should return true if the current user can delete this
     * page. It can be overloaded to customise the security model for an
     * application.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canDelete() returns FALSE on any extension
     * - canEdit() returns FALSE
     * - any descendant page returns FALSE for canDelete()
     *
     * @uses canDelete()
     * @uses ContentModuleExtension->canDelete()
     * @uses canEdit()
     *
     * @param Member $member
     * @return boolean True if the current user can delete this page.
     */
    public function canDelete($member = null)
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CONTENT_MODULE_DELETE"))) {
            return true;
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan('canDelete', $memberID);
        if ($extended !== null) {
            return $extended;
        }

        // If this page no longer exists in stage/live results won't contain the page.
        // Fail-over to false
        return isset($results[$this->ID]) ? $results[$this->ID] : false;
    }

    /**
     * This function should return true if the current user can create new
     * pages of this class. It can be overloaded to customise the security model for an
     * application.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canCreate() returns FALSE on any extension
     * - $can_create is set to FALSE and the site is not in "dev mode"
     *
     * @uses $can_create
     * @uses DataExtension->canCreate()
     *
     * @param Member $member
     * @return boolean True if the current user can create pages on this class.
     */
    public function canCreate($member = null)
    {
        if (!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
            $member = Member::currentUserID();
        }

        if ($member && Permission::checkMember($member, array('ADMIN', 'CONTENT_MODULE_CREATE'))) {
            return true;
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan('canCreate', $member);
        if ($extended !== null) {
            return $extended;
        }

        return $this->stat('can_create') != false || Director::isDev();
    }


    /**
     * This function should return true if the current user can edit this
     * page. It can be overloaded to customise the security model for an
     * application.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canEdit() on any extension returns FALSE
     * @uses DataExtension->canEdit()
     *
     * @param Member $member Set to FALSE if you want to explicitly test permissions without a valid user (useful for unit tests)
     * @return boolean True if the current user can edit this page.
     */
    public function canEdit($member = null)
    {
        if ($member instanceof Member) {
            $memberID = $member->ID;
        } elseif (is_numeric($member)) {
            $memberID = $member;
        } else {
            $memberID = Member::currentUserID();
        }

        if ($memberID && Permission::checkMember($memberID, array("ADMIN", "CONTENT_MODULE_EDIT"))) {
            return true;
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan('canEdit', $memberID);
        if ($extended !== null) {
            return $extended;
        }

        return false;
    }

    /**
     * This function should return true if the current user can publish this
     * page. It can be overloaded to customise the security model for an
     * application.
     *
     * Denies permission if any of the following conditions is TRUE:
     * - canPublish() on any extension returns FALSE
     * - canEdit() returns FALSE
     *
     * @uses ContentModuleExtension->canPublish()
     *
     * @param Member $member
     * @return boolean True if the current user can publish this page.
     */
    public function canPublish($member = null)
    {
        if (!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
            $member = Member::currentUser();
        }

        if ($member && Permission::checkMember($member, array('CONTENT_MODULE_PUBLISH'))) {
            return true;
        }

        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan('canPublish', $member);
        if ($extended !== null) {
            return $extended;
        }

        // Normal case - fail over to canEdit()
        return false;
    }

    public function canDeleteFromLive($member = null)
    {
        // Standard mechanism for accepting permission changes from extensions
        $extended = $this->extendedCan('canDeleteFromLive', $member);
        if ($extended !== null) {
            return $extended;
        }

        return $this->canPublish($member);
    }

    /**
     * Stub method to get the site config, provided so it's easy to override
     */
    public function getSiteConfig()
    {
        if ($this->hasMethod('alternateSiteConfig')) {
            $altConfig = $this->alternateSiteConfig();
            if ($altConfig) {
                return $altConfig;
            }
        }

        return SiteConfig::current_site_config();
    }


    /**
     * CMS action for publishing ContentModule, returns a message
     * @return string
     */
    public function doPublish($fields)
    {
        if ($this->canPublish()) {
            //editing modules
            if (!empty($fields)) {
                foreach ($this->EditFields($fields, false)->dataFields() as $field) {
                    $field->saveInto($this);
                }
                $this->write();
            }

            $original = Versioned::get_one_by_stage("ContentModule", "Live", "\"ContentModule\".\"ID\" = $this->ID");
            if (!$original) {
                $original = new ContentModule();
            }

            // Handle activities undertaken by extensions
            $this->invokeWithExtensions('onBeforePublish', $original);
            $this->publish('Stage', 'Live');

            return $this->Title ? "{$this->Title} ({$this->i18n_singular_name()}) published" : "{$this->i18n_singular_name()} published";
        }

        return "Failed to publish {$this->i18n_singular_name()} ";
    }

    /**
     * CMS action for saving draft version of ContentModule, returns a message
     * @return string
     */
    public function doSave($fields)
    {
        if ($this->canEdit()) {
            if (!empty($fields)) {
                foreach ($this->EditFields($fields, false)->dataFields() as $field) {
                    $field->saveInto($this);
                }

                $this->write();
            }

            return $this->Title ? "{$this->Title} ({$this->i18n_singular_name()}) saved" : "{$this->i18n_singular_name()} saved";
        }

        return "Failed to save {$this->i18n_singular_name()} ";
    }

    /**
     * CMS action for unpublishing ContentModule, returns a message
     * @return string
     */
    public function doUnpublish()
    {
        if ($this->canDeleteFromLive()) {
            $this->extend('onBeforeUnpublish');

            $origStage = Versioned::current_stage();
            Versioned::reading_stage('Live');

            // This way our ID won't be unset
            $clone = clone $this;
            $clone->delete();

            Versioned::reading_stage($origStage);

            return "{$this->Title} unpublished successfully";
        }

        return "Failed to unpublish {$this->i18n_singular_name()} ";
    }

    /**
     * CMS action for deleting draft version of ContentModule, returns a message
     * @return string
     */
    public function doDelete($data)
    {
        if ($this->canDelete()) {
            $this->doUnpublish();
            $this->doUnlink($data);
            $this->delete();
            return "{$this->Title} deleted successfully";
        }

        return "Failed to delete {$this->Title}";
    }

    /**
     * Unlink a module from a page, pass through an array in the format:
     *
     * array(
     *     'PageID' => $pageID,
     *     'Relationship' => 'ContentModules' //many many relationship for ContentModule
     * )
     * @param array $data
     * @return string
     */
    public function doUnlink(array $data)
    {
        if (!empty($data)) {
            if (!empty($data['PageID']) && ($page = Page::get()->byID($data['PageID']))) {
                if (isset($data['Relationship']) && $page->hasMethod($data['Relationship'])) {
                    $page->{$data['Relationship']}()->remove($this);
                } else {
                    $page->ContentModules()->remove($this);
                }

                return "{$this->Title} removed successfully";
            }
        }
        if (isset($_REQUEST['PageID']) && ($pageID = $_REQUEST['PageID'])) {
            /**
             * @var $page Page
             */
            if ($page = Page::get()->byID($pageID)) {
                if ($this->getCurrentModuleField() && $page->hasMethod($this->getCurrentModuleField()->getName())) {
                    $relation = $this->getCurrentModuleField()->getName();
                    $page->{$relation}()->remove($this);
                }
                $page->ContentModules()->remove($this);
                return "{$this->Title} removed successfully";
            }
        }
    }

    public function doRollback($data)
    {
        $this->extend('onBeforeRollback', $data['ID']);

        $id = (isset($data['ID'])) ? (int)$data['ID'] : null;
        $version = (isset($data['Version'])) ? (int)$data['Version'] : null;

        $record = DataObject::get_by_id($this->stat('tree_class'), $id);
        if ($record && !$record->canEdit()) {
            return Security::permissionFailure($this);
        }

        if ($version) {
            $record->doRollbackTo($version);
            $message = _t(
                'CMSMain.ROLLEDBACKVERSIONv2',
                "Rolled back to version #%d.",
                array('version' => $data['Version'])
            );
        } else {
            $record->doRollbackTo('Live');
            $message = _t(
                'CMSMain.ROLLEDBACKPUBv2', "Rolled back to published version."
            );
        }

        return $message;
    }


    /**
     * @todo better way of handling links
     * @param null $action
     * @return mixed
     */
    public function Link($action = null)
    {
        $currentController = Controller::curr();
        $contentController = $currentController instanceof ModuleController ?
            $currentController->currController() :
            $currentController;

        $c = $contentController->Link('m');
        $url = $c . '/' . $this->URLSegment;
        if ($action) {
            $url .= '/' . $action;
        }

        return $url;
    }


    private static $_loaded_modules = array();

    public function setIsActive()
    {
        self::$_loaded_modules[$this->ID] = true;
    }

    public function getIsActive()
    {
        return !empty(self::$_loaded_modules[$this->ID]);
    }

    public function providePermissions()
    {
        return array(
            'CONTENT_MODULE_VIEW' => array(
                'name' => _t('ContentModule.PERMISSION_VIEW_DESCRIPTION', 'View a module'),
                'help' => _t('ContentModule.PERMISSION_VIEW_HELP', 'Allow viewing of a module'),
                'category' => _t('ContentModule.PERMISSIONS_CATEGORY', 'Modules permissions'),
                'sort' => 100
            ),
            'CONTENT_MODULE_EDIT' => array(
                'name' => _t('ContentModule.PERMISSION_EDIT_DESCRIPTION', 'Edit a module'),
                'help' => _t('ContentModule.PERMISSION_EDIT_HELP', 'Allow editing of a module'),
                'category' => _t('ContentModule.PERMISSIONS_CATEGORY', 'Modules permissions'),
                'sort' => 50
            ),
            'CONTENT_MODULE_DELETE' => array(
                'name' => _t('ContentModule.PERMISSION_DELETE_DESCRIPTION', 'Delete a module'),
                'help' => _t('ContentModule.PERMISSION_DELETE_HELP', 'Allow deleting of a module'),
                'category' => _t('ContentModule.PERMISSIONS_CATEGORY', 'Modules permissions'),
                'sort' => 25
            ),
            'CONTENT_MODULE_CREATE' => array(
                'name' => _t('ContentModule.PERMISSION_CREATE_DESCRIPTION', 'Ceate a module'),
                'help' => _t('ContentModule.PERMISSION_CREATE_HELP', 'Allow creating of a module'),
                'category' => _t('ContentModule.PERMISSIONS_CATEGORY', 'Modules permissions'),
                'sort' => 0
            ),
            'CONTENT_MODULE_PUBLISH' => array(
                'name' => _t('ContentModule.PERMISSION_CREATE_DESCRIPTION', 'Publish a module'),
                'help' => _t('ContentModule.PERMISSION_CREATE_HELP', 'Allow publishing of a module'),
                'category' => _t('ContentModule.PERMISSIONS_CATEGORY', 'Modules permissions'),
                'sort' => -25
            ),
        );
    }

    /* WORKFLOW */

    /**
     * @todo handle this better, make use of workflow functions
     * @return string
     */
    public function doStartworkflow()
    {
        $item = $this;

        if (!$item || !$item->canEdit()) {
            return 'You do not have permissions for this workflow';
        }

        $svc = singleton('WorkflowService');
        $svc->startWorkflow($item);

        return 'Workflow started';
    }

    /**
     * Update a workflow based on user input.
     *
     * @todo handle this better, make use of workflow functions
     *
     * @param array $data
     * @param Form $form
     * @param SS_HTTPRequest $request
     * @return String
     */
    public function doUpdateworkflow()
    {
        $data = $_REQUEST;

        $svc = singleton('WorkflowService');
        $p = $this;
        $workflow = $svc->getWorkflowFor($p);
        $action = $workflow->CurrentAction();

        if (!$p || !$p->canEditWorkflow()) {
            return 'You do not have permissions for this workflow';
        }

        $allowedFields = $workflow->getWorkflowFields()->saveableFields();
        unset($allowedFields['TransitionID']);

        $allowed = array_keys($allowedFields);
        if (count($allowed)) {
            $action->update($_REQUEST);
            $action->write();
        }

        if (isset($data['TransitionID']) && $data['TransitionID']) {
            $svc->executeTransition($p, $data['TransitionID']);
        } else {
            // otherwise, just try to execute the current workflow to see if it
            // can now proceed based on user input
            $workflow->execute();
        }

        return 'Workflow updated';
    }

    public function Type()
    {
        return $this->i18n_singular_name();
    }

    // CMS Links
    //--------------------------------------------------------------//

    /**
     * Get the absolute URL for this page, including protocol and host.
     *
     * @param string $action See {@link Link()}
     * @return string
     */
    public function AbsoluteLink($action = null)
    {
        if ($this->hasMethod('alternateAbsoluteLink')) {
            return $this->alternateAbsoluteLink($action);
        } else {
            return Director::absoluteURL($this->Link($action));
        }
    }

    /**
     * Return the link for this {@link SiteTree} object relative to the SilverStripe root.
     *
     * By default, it this page is the current home page, and there is no action specified then this will return a link
     * to the root of the site. However, if you set the $action parameter to TRUE then the link will not be rewritten
     * and returned in its full form.
     *
     * @uses RootURLController::get_homepage_link()
     *
     * @param string $action See {@link Link()}
     * @return string
     */
    public function RelativeLink($action = null)
    {
        $base = $this->URLSegment;

        // Legacy support
        if ($action === true) {
            $action = null;
        }

        return Controller::join_links($base, '/', $action);
    }

    /**
     * Get the absolute URL for this page on the Live site.
     */
    public function getAbsoluteLiveLink($includeStageEqualsLive = true)
    {
        $oldStage = Versioned::current_stage();
        Versioned::reading_stage('Live');
        $live = Versioned::get_one_by_stage('SiteTree', 'Live', '"SiteTree"."ID" = ' . $this->ID);
        if ($live) {
            $link = $live->AbsoluteLink();
            if ($includeStageEqualsLive) {
                $link .= '?stage=Live';
            }
        } else {
            $link = null;
        }

        Versioned::reading_stage($oldStage);
        return $link;
    }

    /**
     * @return String
     */
    public function CMSEditLink()
    {
        return Controller::join_links(singleton('ContentModuleEditController')->Link('show'), $this->ID);
    }

    public function CurrentPage()
    {
        return Controller::curr();
    }

    protected static $_currentModuleFields = array();

    public function setCurrentModuleField($v)
    {
        self::$_currentModuleFields[$this->ID] = $v;
    }

    public function getCurrentModuleField()
    {
        return isset(self::$_currentModuleFields[$this->ID]) ? self::$_currentModuleFields[$this->ID] : ContentModuleField::curr();
    }

    public function getFixTabHeights()
    {
        return static::config()->fix_tab_heights;
    }

    public function getIsEditorMode()
    {
        return (bool)Controller::curr()->getRequest()->getVar('page-editor') && Permission::check('CMS_ACCESS_ContentModulePageEditor');
    }

    public function injectVisualEditor(HTMLText $html)
    {
        $raw = $html->forTemplate();

        //don't do anything if we don't have any html
        if (empty($raw)) {
            return '';
        }

        //add js/css to page to handle clicking on modules, and handle rendering controls etc
        Requirements::javascript(INPAGE_MODULES_DIR . '/javascript/VisualEditor.PreviewHandler.js');
        Requirements::css(INPAGE_MODULES_DIR . '/css/VisualEditor.PreviewHandler.css');

        /**
         * inject editor element inside first element in module
         */

        //turn off errors because DOMDocument doesn't support html5 tags?
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        //prevent DOMDocument adding html wrappers (doctype/head/body)
        $dom->loadHTML($raw,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        //create our editor element
        $editor = $dom->createDocumentFragment();
        $templateHTML = SSViewer::execute_template('VisualEditor_PreviewHandler', $this)->forTemplate();
        $editor->appendXML($templateHTML);

        $dom->documentElement->appendChild($editor);

        $newHTML = $dom->saveHTML();

        //clear the errors
        libxml_clear_errors();

        return DBField::create_field('HTMLText', $newHTML);
    }

    /**
     * A flag provides the user with additional data about the current page status, for example a "removed from draft"
     * status. Each page can have more than one status flag. Returns a map of a unique key to a (localized) title for
     * the flag. The unique key can be reused as a CSS class. Use the 'updateStatusFlags' extension point to customize
     * the flags.
     *
     * Example (simple):
     *   "deletedonlive" => "Deleted"
     *
     * Example (with optional title attribute):
     *   "deletedonlive" => array('text' => "Deleted", 'title' => 'This page has been deleted')
     *
     * @param bool $cached Whether to serve the fields from cache; false regenerate them
     * @return array
     */
    public function getStatusFlags($cached = true)
    {
        if (!$this->_cache_statusFlags || !$cached) {
            $flags = array();
            if ($this->getIsDeletedFromStage()) {
                if ($this->getExistsOnLive()) {
                    $flags['removedfromdraft'] = array(
                        'text' => _t('ContentModule.REMOVEDFROMDRAFTSHORT', 'Removed from draft'),
                        'title' => _t('ContentModule.REMOVEDFROMDRAFTHELP',
                            'Module is published, but has been deleted from draft'),
                    );
                } else {
                    $flags['archived'] = array(
                        'text' => _t('ContentModule.ARCHIVEDPAGESHORT', 'Archived'),
                        'title' => _t('ContentModule.ARCHIVEDPAGEHELP', 'Module is removed from draft and live'),
                    );
                }
            } else if ($this->getIsAddedToStage()) {
                $flags['addedtodraft'] = array(
                    'text' => _t('ContentModule.ADDEDTODRAFTSHORT', 'Draft'),
                    'title' => _t('ContentModule.ADDEDTODRAFTHELP', "Module has not been published yet")
                );
            } else if ($this->getIsModifiedOnStage()) {
                $flags['modified'] = array(
                    'text' => _t('ContentModule.MODIFIEDONDRAFTSHORT', 'Modified'),
                    'title' => _t('ContentModule.MODIFIEDONDRAFTHELP', 'Module has unpublished changes'),
                );
            }

            $this->extend('updateStatusFlags', $flags);

            $this->_cache_statusFlags = $flags;
        }

        return $this->_cache_statusFlags;
    }

    public function getStatusFlagsObj()
    {
        $return = ArrayList::create();

        foreach ($this->getStatusFlags() as $status => $fields) {
            $fields['status'] = $status;
            $return->push(ArrayData::create($fields));
        }

        return $return;
    }

    public function getStatusFlagsKeys()
    {
        return implode(' ', array_keys($this->getStatusFlags()));
    }
}

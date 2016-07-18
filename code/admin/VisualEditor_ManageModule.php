<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 6/07/2016
 * Time: 11:25 AM
 */
class VisualEditor_ManageModule extends VisualEditor
{
    private static $url_segment = 'content-modules/visual-editor/manage';

    private static $tree_class = 'ContentModule';

    private static $url_priority = 43;

    private static $session_namespace = 'VisualEditorModule';

    private static $allowed_actions = array(
        'add',
        'AddForm',
        'delete',
        'module',
        'sort',
        'unlink',
    );

    private static $url_handlers = array(
        'unlink//$ID/$PageID/$Relationship' => 'unlink'
    );

    /**
     * Unlink action for a module
     *
     * @return SS_HTTPResponse
     */
    public function unlink()
    {
        $module = $this->getRecord($this->request->param('ID'));
        $pageID = $this->request->param('PageID');
        $relationship = $this->request->param('Relationship');

        $message = $module->doUnlink(array(
            'PageID' => $pageID,
            'Relationship' => $relationship
        ));

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->module(null, $pageID)
                    ->forTemplate()
            )
        );
    }

    /**
     * Get a database record to be managed by the CMS.
     *
     * @param int $id Record ID
     * @param int $versionID optional Version id of the given record
     * @return DataObject
     */
    public function getRecord($id, $versionID = null) {
        $treeClass = $this->stat('tree_class');

        if($id instanceof $treeClass) {
            return $id;
        }
        else if($id && is_numeric($id)) {
            if($this->getRequest()->getVar('Version')) {
                $versionID = (int) $this->getRequest()->getVar('Version');
            }

            if($versionID) {
                $record = Versioned::get_version($treeClass, $id, $versionID);
            } else {
                $record = DataObject::get_by_id($treeClass, $id);
            }

            // Then, try getting a record from the live site
            if(!$record) {
                // $record = Versioned::get_one_by_stage($treeClass, "Live", "\"$treeClass\".\"ID\" = $id");
                Versioned::reading_stage('Live');
                singleton($treeClass)->flushCache();

                $record = DataObject::get_by_id($treeClass, $id);
                if($record) Versioned::set_reading_mode('');
            }

            // Then, try getting a deleted record
            if(!$record) {
                $record = Versioned::get_latest_version($treeClass, $id);
            }

            // Don't open a page from a different locale
            /** The record's Locale is saved in database in 2.4, and not related with Session,
             *  we should not check their locale matches the Translatable::get_current_locale,
             * 	here as long as we all the HTTPRequest is init with right locale.
             *	This bit breaks the all FileIFrameField functions if the field is used in CMS
             *  and its relevent ajax calles, like loading the tree dropdown for TreeSelectorField.
             */
            /* if($record && SiteTree::has_extension('Translatable') && $record->Locale && $record->Locale != Translatable::get_current_locale()) {
                $record = null;
            }*/

            return $record;

        } else if(substr($id,0,3) == 'new') {
            return $this->getNewItem($id);
        }
    }

    protected function getPageRecord($id)
    {
        return SiteTree::get()->byID($id);
    }

    /**
     * Provides a module manager for adding/sorting modules
     *
     * @return HTMLText
     */
    public function module($request, $id = null)
    {
        $page = $this->getPageRecord($id ? $id : $request->param('ID'));
        $moduleComponents = ArrayList::create();

        //extract the ContentModule relationships
        $manyManys = $page->manyMany();

        if (!empty($manyManys)) foreach ($manyManys as $relationship => $class) {
            if ($class === 'ContentModule' || ($class instanceof ContentModule)) {
                $moduleComponents->push(ArrayData::create(array(
                    'Page' => $page,
                    'Relationship' => $relationship,
                    'Title' => FormField::name_to_label($relationship),
                    'Modules' => $page->{$relationship}()
                )));
            }

        }

        return $this
            ->customise(array('ModuleComponents' => $moduleComponents))
            ->renderWith('VisualEditor_ManageModules');
    }

    /**
     * Action for handling sorting of modules
     *
     * @return SS_HTTPResponse
     */
    public function sort($request) {
        $page = $this->getPageRecord($request->param('ID'));
        $relationship = $this->request->param('OtherID');
        $sort = $this->request->postVar('Sort');

        if ($page && $page->exists() && $relationship && $page->hasMethod($relationship)) {

            foreach ($sort as $moduleID => $index) {
                $SQL_moduleID = Convert::raw2sql($moduleID);
                $SQL_index = Convert::raw2sql($index);

                list($parentClass, $componentClass, $parentField, $componentField, $table) = $relationshipInfo = $page->manyManyComponent($relationship);

                //updates the join table directly
                $queries[] = "UPDATE \"{$table}\" set \"Sort\" = '{$SQL_index}' WHERE \"{$parentField}\" = '{$page->ID}' AND \"{$componentField}\" = '{$SQL_moduleID}'";
                DB::query(
                    "UPDATE \"{$table}\" set \"Sort\" = '{$SQL_index}' WHERE \"{$parentField}\" = '{$page->ID}' AND \"{$componentField}\" = '{$SQL_moduleID}'"
                );
            }

            return ContentModuleUtilities::json_response(
                array(
                    'Status' => 1,
                    'Message' => 'Module order updated'
                )
            );
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => 'Unable to update modules'
            )
        );
    }

    /**
     * Get a list of module types
     *
     * returns format
     * [
     *     'ClassName' => 'Class',
     *     'AddAction' => 'Action',
     *     'Description  => 'Description'
     * ]
     *
     * @return ArrayList|DataList
     */
    public function ModuleTypes($class = 'ContentModule')
    {

        if ( ! ($class instanceof ContentModule)) $class = 'ContentModule';

        $moduleTypes = $class::content_module_types();

        $result = new ArrayList();

        foreach($moduleTypes as $name => $instance) {

            if($instance instanceof HiddenClass) continue;

            // skip this type if it is restricted
            if($instance->stat('need_permission') && !$this->can(singleton($class)->stat('need_permission'))) continue;

            $addAction = $instance->i18n_singular_name();

            // Get description (convert 'Page' to 'SiteTree' for correct localization lookups)
            $description = _t($instance->class . '.DESCRIPTION');

            if(!$description) {
                $description = $instance->uninherited('description');
            }

            $result->push(new ArrayData(array(
                'ClassName' => $instance->class,
                'AddAction' => $addAction,
                'Description' => $description,

                // TODO Sprite support
                'IconURL' => $instance->stat('icon'),
                'Title' => $name,
            )));
        }

        $result = $result->sort('AddAction');
        return $result;
    }

    /**
     * @return Form
     */
    public function AddForm($request, $relationship = '', $page = null)
    {

        if (!$relationship) $relationship = $this->request->requestVar('Relationship');
        if (!$page) $page = $this->CurrentPage($this->request->requestVar('PageID'));

        list($parentClass, $componentClass, $parentField, $componentField, $table) = $page->manyManyComponent($relationship);

        $moduleTypes = array();
        foreach($this->ModuleTypes($componentClass) as $type) {
            $html = sprintf('<span class="module-icon class-%s"></span><strong class="title">%s</strong><span class="description">%s</span>',
                $type->getField('ClassName'),
                $type->getField('AddAction'),
                $type->getField('Description')
            );
            $moduleTypes[$type->getField('ClassName')] = $html;
        }

        $numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><span class="title">%s</span></span>';

        $fields = new FieldList(
            $typeField = new OptionsetField(
                "ModuleType",
                _t('ContentModule.ChooseModuleType', 'Choose module type'),
                $moduleTypes
            ),
            HiddenField::create('Relationship', '', $relationship),
            HiddenField::create('PageID', '', $page->ID)
        );

        $actions = new FieldList(
            FormAction::create("doAdd", _t('CMSMain.Create',"Create"))
                ->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true),
            FormAction::create("doCancel", _t('CMSMain.Cancel',"Cancel"))
                ->addExtraClass('ss-ui-action-destructive ss-ui-action-cancel cancel')
                ->setUseButtonTag(true)
        );

        $this->extend('updateModuleOptions', $fields);

        $form = CMSForm::create(
            $this, "AddForm", $fields, $actions
        )->setHTMLID('Form_ModuleAddForm');

        $form->addExtraClass('stacked cms-content ' . $this->BaseCSSClasses());
        $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

        return $form;
    }

    public function doAdd($data, Form $form)
    {
        $page = $this->CurrentPage($data['PageID']);
        $relationship = $data['Relationship'];

        if (!empty($data['ModuleType']) && (singleton($data['ModuleType']) instanceof ContentModule)) {
            $module = new $data['ModuleType'];
            $module->write();

            $page->{$relationship}()->add($module);

            return $this->redirect(Controller::join_links($this->EditLink($module->ID)));
        }

        return ContentModuleUtilities::json_response(array(
            'Status' => 0,
            'Message' => 'Couldn\'t add new module ' . $data['ModuleType']
        ));
    }

    /**
     * Action for adding a module, returns the AddForm
     *
     * @return SS_HTTPResponse
     */
    public function add()
    {
        $page = $this->CurrentPage();
        $relationship = $this->request->param('OtherID');

        if ($page && $page->exists() && $relationship && $page->hasMethod($relationship)) {
            return $this->AddForm(null, $relationship, $page)
                ->forTemplate()
                ->forTemplate();
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => 'Unable to add a new module'
            )
        );
    }

    public function EditLink($id)
    {
        return Controller::join_links(singleton('VisualEditor_EditModule')->Link('show'), $id);
    }
}
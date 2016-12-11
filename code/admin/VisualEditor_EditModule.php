<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 6/07/2016
 * Time: 11:25 AM
 */
class VisualEditor_EditModule extends VisualEditor
{
    private static $url_segment = 'content-modules/visual-editor/module';

    private static $tree_class = 'ContentModule';

    private static $url_priority = 42;

    private static $session_namespace = 'VisualEditorModule.EditModule';

    private static $allowed_actions = array(
        'delete',
        'ModuleEditForm',
        'publish',
        'save',
        'show',
        'unlink',
        'unpublish',
    );


    public function init()
    {
        parent::init();
    }

    /**
     * Shows edit form for a module
     *
     * @param $request
     * @return HTML
     */
    public function show($request)
    {
        if($request->param('ID')) $this->setCurrentPageID($request->param('ID'));
        return $this->getModuleEditForm($this->getRequest()->param('ID'))->forTemplate();
    }

    /**
     * @param null|int $id
     * @return Form
     */
    public function getModuleEditForm($id = null, $fields = null)
    {
        if(!$id) $id = $this->currentPageID();

        $module = $this->getRecord($id);

        $fields = $module->getCMSFields();
        $fields->push(HiddenField::create('ID')->setValue($id));

        $actions = $module->getCMSActions();

        $form = Form::create($this, 'ModuleEditForm', $fields, $actions);
        $form->loadDataFrom($module)
            ->setTemplate('VisualEditor_EditForm')
            ->addExtraClass('module-edit-form cms-content');

        return $form;
    }

    /**
     * returns getModuleEditForm
     *
     * @return Form
     */
    public function ModuleEditForm()
    {
        return $this->getModuleEditForm();
    }

    /**
     * Action for publishing a module
     *
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function publish($data, $form)
    {
        $module = $this->getRecord($data['ID']);
        $message = $module->doPublish($data);

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->getModuleEditForm($data['ID'])
                    ->forTemplate()
                    ->forTemplate()
            )
        );
    }

    /**
     * Unpublish action for a module
     *
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function unpublish($data, $form)
    {
        $module = $this->getRecord($data['ID']);

        $message = $module->doUnpublish($data);

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->getModuleEditForm($data['ID'])
                    ->forTemplate()
                    ->forTemplate()
            )
        );
    }

    /**
     * Save action for a module (save draft)
     *
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function save($data, $form)
    {
        $module = $this->getRecord($data['ID']);

        $message = $module->doSave($data);

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->getModuleEditForm($data['ID'])
                    ->forTemplate()
                    ->forTemplate()
            )
        );
    }

    /**
     * Delete action for a module
     *
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function delete($data, $form)
    {
        $module = $this->getRecord($data['ID']);

        $message = $module->doDelete($data);

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->getModuleEditForm($data['ID'])
                    ->forTemplate()
                    ->forTemplate()
            )
        );
    }

    /**
     * Unlink action for a module
     *
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function unlink($data, $form)
    {
        $module = $this->getRecord($data['ID']);

        $message = $module->doUnlink($data);

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->getModuleEditForm($data['ID'])
                    ->forTemplate()
                    ->forTemplate()
            )
        );
    }

    /**
     * Unlink action for a module
     *
     * @param $data
     * @param $form
     * @return SS_HTTPResponse
     */
    public function rollback($data, $form)
    {
        $module = $this->getRecord($data['ID']);

        $message = $module->doRollback($data);

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 1,
                'Message' => $message,
                'Content' => $this->getModuleEditForm($data['ID'])
                    ->forTemplate()
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

            if (class_exists('Translatable')) {
                Translatable::disable_locale_filter();
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

            if (class_exists('Translatable')) {
                Translatable::enable_locale_filter();
            }

            return $record;

        } else if(substr($id,0,3) == 'new') {
            return $this->getNewItem($id);
        }
    }

    /**
     *
     * @return String
     */
    public function BackLink()
    {
        return Controller::join_links($this->Link('show'), $this->currentPageID());
    }

}
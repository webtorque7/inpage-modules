<?php

/**
 * A field for editing @link ContentModule on a page
 * N.B. If setting links on sub-fields, make sure to set OtherID for this, otherwise the action
 * for the sub-field will be shifted off the stack
 *
 * Date: 25/04/13
 * Time: 2:14 PM
 */
class ContentModuleField extends FormField
{

    protected static $curr = null;

    private static $url_handlers = array(
        'modulefield/$ID' => 'modulefield',
        '$Action!/$ID/$OtherID' => '$Action'
    );

    private static $allowed_actions = array(
        'addNewModule',
        'addExistingModule',
        'getExistingModules',
        'copyModule',
        'module',
        'sort',
        'modulefield',
        'reload'
    );

    public function __construct($name, $title = null, $value = null)
    {
        parent::__construct($name, $title, $value);

        self::$curr = $this;
    }

    public function __call($method, $arguments)
    {
        if ($id = $this->request->param('$ID')) {
            if ($module = ContentModule::get()->byID($id)) {
                $action = $this->request->param('Action');

                if ($module->hasMethod($action)) {
                    $message = $module->{$action}($arguments);
                }
            }
        } else {
            parent::__call($method, $arguments);
        }
    }

    public function saveInto(DataObjectInterface $record)
    {
        /*if (isset($_REQUEST['ContentModule'])) {
                foreach ($_REQUEST['ContentModule'] as $id => $fields) {
                        $module = ContentModule::get()->byID($id);

                        if ($module) {
                                $module->update($fields);
                                $module->write();

                                $record->ContentModules()->add($module);
                        }
                }
        }*/
    }


    public function getRelationshipClass()
    {
        return $this->getRecord()->getRelationClass($this->getName());
    }

    /**
     * @todo Internationalisation
     * @return string
     */
    public function FieldHolder($properties = array())
    {
        Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');

        Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
        Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
        Requirements::javascript(FRAMEWORK_DIR . '/javascript/ToggleCompositeField.js');
        Requirements::javascript(INPAGE_MODULES_DIR . '/javascript/ContentModuleField.js');

        Requirements::css(FRAMEWORK_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');


        return $this->renderWith('ContentModuleField');
    }

    public function AvailableModules()
    {
        $modules = call_user_func(array($this->getRelationshipClass(), 'content_module_types'));
        return new ArrayList($modules);
    }

    public function CurrentModules()
    {
        $record = $this->getRecord();

        self::$curr = $this;

        if ($record) {
            $modules = $record->{$this->getName()}();

            //permission check
            if ($modules->count()) {
                foreach ($modules as $module) {
                    if (!$module->canEdit(Member::currentUser())) {
                        unset($module);
                    } else {
                        $module->form = $this->getForm();
                        $module->setCurrentModuleField($this);
                        //var_dump($module->getCurrentModuleField());exit;
                    }
                }
            }

            return $modules;
        }

        return false;
    }

    public function getRecord()
    {
        return $this->getForm()->getRecord();
    }

    public function handleAction($request, $action)
    {
        $this->extend('onBeforeHandleAction', $request, $action);
        if ($this->hasMethod($action)) {
            return $this->{$action}($request);
        }
    }

    public function addNewModule()
    {
        if (($moduleType = $this->request->param('ID')) && ($pageID = $this->request->param('OtherID'))) {
            if (is_subclass_of($moduleType, 'ContentModule') && ($page = Page::get()->byID($pageID))) {
                $module = new $moduleType;
                $module->write();

                $page->{$this->getName()}()->add(
                    $module,
                    array('Sort' => $page->{$this->getName()}()->max('Sort') + 1)
                );

                return ContentModuleUtilities::json_response(
                    array(
                        'Status' => 1,
                        'Content' => $module->EditForm()->RAW(),
                        'Message' => "{$moduleType} created"
                    )
                );
            }
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => "There was an error creating module '{$moduleType}'"
            )
        );
    }

    public function copyModule()
    {
        if (($moduleID = $this->request->param('ID')) && ($pageID = $this->request->param('OtherID'))) {
            $module = ContentModule::get()->byID($moduleID);

            if ($module && ($page = Page::get()->byID($pageID))) {
                $newModule = $module->duplicate(true);

                $page->{$this->getName()}()->add(
                    $newModule,
                    array('Sort' => $page->{$this->getName()}()->max('Sort') + 1)
                );

                return ContentModuleUtilities::json_response(
                    array(
                        'Status' => 1,
                        'Content' => $newModule->EditForm()->RAW(),
                        'Message' => "{$newModule->Title} copied"
                    )
                );
            }
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => "There was an error copying the module to the current page"
            )
        );
    }

    public function addExistingModule()
    {
        if (($moduleID = $this->request->param('ID')) && ($pageID = $this->request->param('OtherID'))) {
            $module = ContentModule::get()->byID($moduleID);

            if ($module && ($page = Page::get()->byID($pageID))) {
                $page->{$this->getName()}()->add(
                    $module,
                    array('Sort' => $page->{$this->getName()}()->max('Sort') + 1)
                );

                return ContentModuleUtilities::json_response(
                    array(
                        'Status' => 1,
                        'Content' => $module->EditForm()->RAW(),
                        'Message' => "{$module->Title} added"
                    )
                );
            }
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => "There was an error adding the module to the current page"
            )
        );
    }

    /**
     * Returns existing modules for a ContentModule type
     * @todo Remove modules already in use by this Page
     * @return SS_HTTPResponse
     */
    public function getExistingModules()
    {
        if ($moduleType = $this->request->param('ID')) {
            if (is_subclass_of($moduleType, 'ContentModule')) {
                $not = '';
                if ($record = $this->getRecord()) {
                    $existingModules = $record->{$this->getName()}();

                    if ($existingModules->count()) {
                        //todo: more efficient way to do this
                        $not = 'AND "ContentModule"."ID" NOT IN (' . implode(
                                ',',
                                array_keys($existingModules->map()->toArray())
                            ) . ')';
                    }
                }

                $modules = DataObject::get($moduleType, '"Reuseable" = 1 ' . $not, '"Title" ASC');


                //security
                if ($modules->count()) {
                    foreach ($modules as $module) {
                        if (!$module->canEdit(Member::currentUser())) {
                            unset($module);
                        }
                    }

                    $modulesArray = array();

                    //convert to array for json
                    foreach ($modules as $module) {
                        $modulesArray[] = array('ID' => $module->ID, 'Title' => $module->Title);
                    }

                    return ContentModuleUtilities::json_response(
                        array(
                            'Status' => 1,
                            'Modules' => $modulesArray
                        )
                    );
                }
            }
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => 'No modules could be found'
            )
        );
    }

    /**
     * Sorts ContentModules for a given Page
     * @return SS_HTTPResponse
     */
    public function sort()
    {
        if (($id = $this->request->param('ID')) && !empty($_REQUEST['Sort'])) {

            // Debug::dump($_REQUEST['Sort']);exit;
            $SQL_id = Convert::raw2sql($id);
            foreach ($_REQUEST['Sort'] as $moduleID => $index) {
                $SQL_moduleID = Convert::raw2sql($moduleID);
                $SQL_index = Convert::raw2sql($index);

                list($parentClass, $componentClass, $parentField, $componentField, $table) = $this->getRecord(
                )->many_many($this->getName());

                $queries[] = "UPDATE \"{$table}\" set \"Sort\" = '{$SQL_index}' WHERE \"{$parentField}\" = '{$SQL_id}' AND \"{$componentField}\" = '{$SQL_moduleID}'";
                DB::query(
                    "UPDATE \"{$table}\" set \"Sort\" = '{$SQL_index}' WHERE \"{$parentField}\" = '{$SQL_id}' AND \"{$componentField}\" = '{$SQL_moduleID}'"
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
                'Message' => 'No modules to sort'
            )
        );
    }

    public function reload()
    {
        if ($id = $this->request->param('ID')) {
            $module = ContentModule::get()->byID($id);
            return ContentModuleUtilities::json_response(
                array(
                    'Status' => 1,
                    'Content' => $module->EditForm()->RAW()
                )
            );
        }
    }

    /**
     * Handles all actions for the individual ContentModule, action is passed on with "do" prepended
     * e.g. publish becomes ContentModule->doPublish
     *
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     */
    public function module($request)
    {
        if (($action = $this->request->param('ID')) && ($id = $this->request->param('OtherID'))) {
            $module = ContentModule::get()->byID($id);
            $method = 'do' . ucfirst($action);

            if ($module && $module->hasMethod($method)) {
                $module->setCurrentModuleField($this);
                $postFields = $request->requestVar('ContentModule');
                $contentFields = !empty($postFields[$id]) ? $postFields[$id] : null;

                if (!empty($contentFields)) {

                    //set booleans to false if not set
                    //@todo this looks like a hack
                    $fields = $module->db();
                    foreach ($fields as $field => $type) {
                        if ($type == 'Boolean' && !isset($contentFields[$field])) {
                            $contentFields[$field] = false;
                        }
                    }
                }

                $message = $module->$method($contentFields);

                return ContentModuleUtilities::json_response(
                    array(
                        'Status' => 1,
                        'Message' => $message,
                        'Content' => $module->EditForm()->RAW()
                    )
                );
            }
        }

        return ContentModuleUtilities::json_response(
            array(
                'Status' => 0,
                'Message' => 'There was a problem with your request'
            )
        );
    }


    /**
     * Handles actions on fields for the module (e.g. uploadfield)
     * Expects the name of the field to be in the ID param (in the form ContentModule[ModuleID][FieldName],
     * and the action to be in the OtherID param
     */
    public function modulefield()
    {
        if (($fieldName = $this->request->param('ID'))) {
            $matches = array();
            if (preg_match('/ContentModule\[([0-9]{1,})\]/i', $fieldName, $matches)) {
                $moduleID = $matches[1];

                $module = ContentModule::get()->byID($moduleID);

                if ($module) {
                    //get original field name
                    $matches2 = array();
                    preg_match('/ContentModule\[[0-9]{1,}\]\[([a-zA-Z0-9]{1,})\\]/i', $fieldName, $matches2);
                    $originalFieldName = (!empty($matches2[1])) ? $matches2[1] : '';

                    //find the field
                    $fields = $module->EditFields()->dataFields();

                    if ($fields) {
                        foreach ($fields as $field) {
                            if ($field->getName() == $fieldName) {
                                //setup field name(s)
                                if ($field->hasMethod('setContentModuleNames')) {
                                    $field->setContentModuleNames($originalFieldName, $fieldName);
                                } else {
                                    $field->setName($originalFieldName);
                                }

                                //set record if required
                                if ($field->hasMethod('setRecord')) {
                                    $field->setRecord($module);
                                }

                                return $field;

                                //check if there is a url handler for this action
                                /* $handlers = $field->stat('url_handlers');
                                 if ($handlers && !empty($handlers[$action])) {
                                         return $field->{$handlers[$action]}($this->request);
                                 }
                                 else if ($field->hasMethod($action)) {
                                         return $field->{$action}($this->request);
                                 }*/
                            }
                        }
                    }
                }
            }
        }
        return $this->httpError(404);
    }


    public static function curr()
    {
        return self::$curr;
    }
}

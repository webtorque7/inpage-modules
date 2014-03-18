<?php
class ContentModuleRelationshipEditor extends FormField
{
        protected $_relationship;
        protected $record;
        protected $_fieldList;
        protected $_originalName;
        protected $_idField;
        protected $_canEdit = true;

        protected $showAddButton = false;
        protected $showAddExistingButton = false;
        protected $showDeleteButton = true;

        protected $sortField = null;

	protected $maxItems = null;

	private static $url_handlers = array(
                'remove/$ID' => 'remove',
		'deleteitem/$ID' => 'deleteitem',
                'edititem/$ID' => 'edititem',
                'existingitem/$ID' => 'existingitem',
                'ItemEditForm' => 'ItemEditForm',
                '$Action!/$ID/$OtherID' => '$Action',

        );

        private static $allowed_actions = array(
                'remove',
                'ItemEditForm',
                'edititem',
		'deleteitem',
                'additem',
                'newitem',
                'existingitem',
                'sort',
		'reload'
        );

        public function __construct($name, $title = null, $relationship = null, $record = null, $fieldList = null) {
                parent::__construct($name, $title, null);

                $this->_relationship = $relationship;
                $this->record = $record;

                if ($fieldList) {
                        $this->_fieldList = $fieldList;
                } else if ($className = $this->getItemClassName()) {
                        $this->_fieldList = singleton($className)->summaryFields();
                }
        }

        public function Link($action = null) {
                $cModField = ContentModuleField::curr();
                $link = '';
                if ($cModField) {
                        $link = $cModField->Link('modulefield') . '/' . $this->getName();
                        if ($action) $link .= '/' . $action;
                } else {
                        $link = parent::Link($action);
                }

                return $link;
        }

        public function setContentModuleNames($originalFieldName, $fieldName, $id = null) {
                if ($id) $this->_idField = $id;
                $this->setName($fieldName);
                $this->_originalName = $originalFieldName;
                return $this;
        }

        public function isManyMany() {
                return ($this->record && $this->record->many_many($this->_relationship)) ? true : false;
        }

        public function isHasMany() {
                return ($this->record && $this->record->has_many($this->_relationship)) ? true : false;
        }

        /**
         * @param $bool Bool
         */
        public function setCanEdit($bool) {
                $this->_canEdit = $bool;
                return $this;
        }

        public function getCanEdit() {
                return $this->_canEdit;
        }

        /**
         * Set add button to visible
         * @param $bool
         */
        public function setShowAddButton($bool) {
                $this->showAddButton = $bool;
                return $this;
        }

        public function getShowAddButton() {
                return $this->showAddButton;
        }

        /**
         * Set add existing button to visible
         * @param $bool
         */
        public function setShowAddExistingButton($bool) {
                $this->showAddExistingButton = $bool;
                return $this;
        }

        public function getShowAddExistingButton() {
                return $this->showAddExistingButton;
        }

        /**
        * Set add button to visible
        * @param $bool
        */
        public function setShowDeleteButton($bool) {
                $this->showDeleteButton = $bool;
                return $this;
        }

        public function getShowDeleteButton() {
                return $this->showDeleteButton;
        }

        public function getExistingItems() {
                $items = $this->getSource();

                $not = '';
                if ($items && $items->count()) {
                        $ids = array();

                        foreach ($items as $item) {
                                $ids[] = $item->ID;
                        }
                        $sIDS = implode(',', $ids);

                        $baseTable = singleton($this->getItemClassName())->baseTable();

                        $not = "\"{$baseTable}\".\"ID\" NOT IN ({$sIDS})";
                }

                return DataObject::get($this->getItemClassName(), $not);
        }

        public function getExistingDropdown() {
                //$drop = new DropdownField('ExistingItems', 'Existing Items', $this->getExistingItems()->map(), null, null, 'Select Existing Item');
	        $drop = DropdownField::create('ExistingItems', 'Existing Items', $this->getExistingItems()->map(), null, null)
		       ->setEmptyString('Select Existing Item')
		       ->addExtraClass('no-change-track');
                $html = str_replace('id="ExistingItems"', 'id="ExistingItems_' . rand() . '"', $drop->forTemplate()->RAW());
                return $html;
        }

        /**
         * Set the sort field, if set, drag and drop sorting is enabled
         * @param $field
         * @return $this
         */
        public function setSortField($field) {
                $this->sortField = $field;
                return $this;
        }

        public function getSortField() {
                return $this->sortField;
        }

        /**
         * Action for handling sorting of the items in the relationship
         * @return SS_HTTPResponse
         */
        public function sort() {
                if (!empty($_REQUEST['Sort'])) {

                        foreach ($_REQUEST['Sort'] as $id => $index) {

                                //many many or has many
                                if ($this->isManyMany()) {
                                        $SQL_id = Convert::raw2sql($id);
                                        $SQL_index = Convert::raw2sql($index);

	                                list($parentClass, $componentClass, $parentField, $componentField, $table) = $this->getRecord()->many_many($this->_relationship);

                                        DB::query("UPDATE \"{$table}\" set \"{$this->getSortField()}\" = '{$SQL_index}' WHERE \"{$parentField}\" = '{$this->getRecord()->ID}' AND \"{$componentField}\" = '{$SQL_id}'");
                                }
                                else if ($this->isHasMany()) { //todo: versioned?
                                        $SQL_id = Convert::raw2sql($id);
                                        $SQL_index = Convert::raw2sql($index);

                                        $table = $this->getItemClassName();

                                        DB::query("UPDATE \"{$table}\" set \"{$this->getSortField()}\" = '{$SQL_index}' WHERE ID = '{$SQL_id}'");
                                }
                        }

                        return ContentModuleUtilities::json_response(array(
                                'Status' => 1,
                                'Message' => "{$this->_relationship} order updated",
                        ));
                }

                return ContentModuleUtilities::json_response(array(
                        'Status' => 0,
                        'Message' => 'Nothing to sort'
                ));
        }

	/**
	 * Returns the field template to update
	 * @return SS_HTTPResponse
	 */
	public function reload() {
		return ContentModuleUtilities::json_response(array(
			'Status' => 1,
			'Content' => $this->forTemplate()->RAW()
		));
	}

        /**
         * Get the form, checks for a current ContentModuleField first
         * @return Form
         */
        public function getForm() {
                $cModField = ContentModuleField::curr();

                if ($cModField) return $cModField->getForm();

                return parent::getForm();
        }

        public function getRelation() {
                return $this->_relationship;
        }

	// Use this to count the number of items
        public function getSource() {
                return ($this->record && $this->record->hasMethod($this->_relationship)) ? $this->record->{$this->_relationship}() : null;
        }

	public function getSourceCount(){
		return $this->getSource()->count();
	}


        public function getItems() {
                if ($this->record && $this->record->hasMethod($this->_relationship)) {
                        $items = $this->getSource();
                        //return $items;
                        $return = new ArrayList();

                        if ($items && $items->count()) foreach ($items as $item) {
                                $return->push(ContentModuleRelationshipEditor_Item::create($this, $item)->setCanEdit($this->getCanEdit()));
                        }

                        return $return;
                }

                user_error(sprintf('Relationship %s doesn\'t exist on class %s', $this->_relationship, $this->record->ClassName));
        }

        public function setRecord($record) {
                $this->record = $record;
        }

        public function getRecord() {
                return $this->record;
        }


        public function FieldHolder($properties = array()) {
                Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');

                Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
                Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
                Requirements::javascript(INPAGE_MODULES_DIR . '/javascript/ContentModuleRelationshipEditor.js');

                Requirements::css(INPAGE_MODULES_DIR . '/css/ContentModuleRelationshipEditor.css');

                return $this->renderWith('ContentModuleRelationshipEditor');
        }

        /**
         * Get the list of fields from DataObject::$summary_fields
         * @return mixed
         */
        public function FieldList() {
                return $this->_fieldList;
        }

        /**
         * Get the header fields
         * @return ArrayList
         */
        public function Header() {
                $headers = new ArrayList();

                foreach ($this->FieldList() as $field => $title) {
                        $headers->push(new ArrayData(array(
                                'Name' => $field,
                                'Title' > $title
                        )));
                }

                return $headers;
        }

        public function NoColumns() {
                $count = count($this->_fieldList) + 1;
                if ($this->getSortField()) $count++;
                return $count;
        }

        public function remove(SS_HTTPRequest $request) {

                $id = $request->param('ID');;
                if (!$id) $id = $request->param('OtherID');

                if ($id) {
                        if ($this->record && $this->record->hasMethod($this->_relationship)) {
                                $this->record->{$this->_relationship}()->removeByID($id);

                                return ContentModuleUtilities::json_response(array(
                                        'Status' => 1,
                                        'Message' => "Item removed from {$this->Title()}",
                                        'Content' => $this->forTemplate()->RAW()
                                ));
                        }
                }

                return ContentModuleUtilities::json_response(array(
                        'Status' => 0,
                        'Message' => "Unable to find item"
                ));
        }

	public function deleteitem(SS_HTTPRequest $request) {

		$id = $request->param('ID');;
		if (!$id) $id = $request->param('OtherID');

		if ($id) {
			if ($this->record && $this->record->hasMethod($this->_relationship)) {
				$this->record->{$this->_relationship}()->removeByID($id);

				$obj = DataObject::get_by_id($this->getItemClassName(), $id);

				if ($obj) {
					$obj->delete();
				}

				return ContentModuleUtilities::json_response(array(
					'Status' => 1,
					'Message' => "Item {$obj->Title} deleted",
					'Content' => $this->forTemplate()->RAW()
				));
			}
		}

		return ContentModuleUtilities::json_response(array(
			'Status' => 0,
			'Message' => "Unable to find item"
		));
	}

        /*public function addform() {
                if ($this->record) {
                        $className = $this->record->getRelationClass($this->_relationship);

                        if ($className) {
                                return $this->getAddForm()
                        }
                }
        }*/

        public function edititem(SS_HTTPRequest $request) {
                $id = $request->param('ID');
                if (!$id) $id = $request->param('OtherID');

                if ($id) {
                        if ($form = $this->ItemEditForm($id)) {
                                return ContentModuleUtilities::json_response(array(
                                        'Status' => 1,
                                        'Content' => $form->forAjaxTemplate()
                                ));
                        }
                }

                return ContentModuleUtilities::json_response(array(
                        'Status' => 0,
                        'Message' => 'Form could not be loaded'
                ));
        }

        protected function getItemClassName() {
                if ($this->record) {
                        return $this->record->getRelationClass($this->_relationship);
                }

                return false;
        }

        public function newitem() {
		//clear session id so it doesn't return existing record
		$this->setSessionID(null);

                if ($form = $this->ItemEditForm()) {
                        return ContentModuleUtilities::json_response(array(
                                'Status' => 1,
                                'Content' => $form->forAjaxTemplate()->RAW()
                        ));
                }

                return ContentModuleUtilities::json_response(array(
                        'Status' => 0,
                        'Message' => 'Form could not be loaded'
                ));
        }

        public function existingitem() {
                if (($id = $this->request->param('ID')) && $this->record) {
                        $item = DataObject::get_by_id($this->getItemClassName(), $id);

                        if ($item) {
                                $this->record->{$this->_relationship}()->add($item);

                                return ContentModuleUtilities::json_response(array(
                                        "Status" => 1,
                                        "Message" => "{$item->Title} added successfully",
                                        "Content" => $this->forTemplate()->RAW()
                                ));
                        }
                }

                return ContentModuleUtilities::json_response(array(
                        "Status" => 0,
                        "Message" => "Unable to add item"
                ));
        }

	/**
	 * Session key for storing the item ID
	 * @return string
	 */
	public function getSessionKey() {
		return 'CMRE.' . $this->getName() . '.ID';
	}

	/**
	 * Stores ID in Session so it can be used for item lookup
	 * in subsequent requests
	 */
	public function setSessionID($id) {
		Session::set($this->getSessionKey(), $id);
	}

	/**
	 * Gets ID for an item from previous request stored in Session
	 * in subsequent requests
	 * @return string
	 */
	public function getSessionID() {
		return Session::get($this->getSessionKey());
	}

        /**
         * Calls {@link DataObject->getCMSFields()}
         *
         * @param Int $id
         * @param FieldList $fields
         * @return Form
         */
        public function ItemEditForm($id = null, $fields = null) {

                if ($this->record) {
                        $className = $this->getItemClassName();

                        $record = null;

                        if ($id && is_numeric($id)) {
                                $record = DataObject::get_by_id($className, (int)$id);
                        }
                        else if (!empty($_REQUEST['RecordID'])) {
                                $record = DataObject::get_by_id($className, (int)$_REQUEST['RecordID']);
                        }
			else if (!empty($_REQUEST['ID'])) {
				$record = DataObject::get_by_id($className, (int)$_REQUEST['ID']);
			}
			else if ($this->_idField) {
                                $record = DataObject::get_by_id($className, (int)$this->_idField);
                        }
			else if ($id = $this->getSessionID()) {
				$record = DataObject::get_by_id($className, $id);
			}

                        if (!$record) {
                                $record = new $className;
                        }

                        $fields = ($fields) ? $fields : $record->getCMSFields();
                        if ($fields == null) {
                                user_error(
                                        "getCMSFields() returned null  - it should return a FieldList object.
                                        Perhaps you forgot to put a return statement at the end of your method?",
                                        E_USER_ERROR
                                );
                        }

                        if ($record->hasMethod('getAllCMSActions')) {
                                $actions = $record->getAllCMSActions();
                        } else {
                                $actions = $record->getCMSActions();
                                // add default actions if none are defined
                                if (!$actions || !$actions->Count()) {
                                        if ($record->hasMethod('canEdit') && $record->canEdit()) {
                                                $actions->push(
                                                        FormAction::create('save', _t('CMSMain.SAVE', 'Save'))
                                                                ->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
                                                );
                                        }
                                        if ($record->hasMethod('canDelete') && $record->canDelete() && $record->exists()) {
                                                $actions->push(
                                                        FormAction::create('delete', _t('ModelAdmin.DELETE', 'Delete'))
                                                                ->addExtraClass('ss-ui-action-destructive')
                                                );
                                        }
                                }
                        }

                        // Use <button> to allow full jQuery UI styling
                        $actionsFlattened = $actions->dataFields();
                        if ($actionsFlattened) foreach ($actionsFlattened as $action) $action->setUseButtonTag(true);

                        $form = new Form($this, "ItemEditForm", $fields, $actions);
                        $form->addExtraClass('cms-edit-form ContentRelationshipEditor_Form');
                        $form->setAttribute('data-pjax-fragment', 'CurrentForm');

                        // Set this if you want to split up tabs into a separate header row
                        // if($form->Fields()->hasTabset()) {
                        // 	$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
                        // }

                        // Add a default or custom validator.
                        // @todo Currently the default Validator.js implementation
                        //  adds javascript to the document body, meaning it won't
                        //  be included properly if the associated fields are loaded
                        //  through ajax. This means only serverside validation
                        //  will kick in for pages+validation loaded through ajax.
                        //  This will be solved by using less obtrusive javascript validation
                        //  in the future, see http://open.silverstripe.com/ticket/2915 and
                        //  http://open.silverstripe.com/ticket/3386
                        if ($record->hasMethod('getCMSValidator')) {
                                $validator = $record->getCMSValidator();
                                // The clientside (mainly LeftAndMain*.js) rely on ajax responses
                                // which can be evaluated as javascript, hence we need
                                // to override any global changes to the validation handler.
                                $form->setValidator($validator);
                        } else {
                                $form->unsetValidator();
                        }

                        if ($record->hasMethod('canEdit') && !$record->canEdit()) {
                                $readonlyFields = $form->Fields()->makeReadonly();
                                $form->setFields($readonlyFields);
                        }

                        if ($record->exists()) {
                                //rename to recordID so it doesn't conflict with CMSMain/LeftAndMain
                                $fields->push(new HiddenField('RecordID', 'RecordID', $record->ID));

				//store in session so we can use for subfields
				$this->setSessionID($record->ID);
                        }
                        $form->loadDataFrom($record);

			//echo $form->getRecord()->ID;exit;
                        $form->setFormAction($this->Link('ItemEditForm'));

                        return $form;
                }

                return false;
        }

        public function save($data, Form $form) {
                if ($this->record) {
                        $className = $this->getItemClassName();

                        //check for an existing record
                        $record = null;
                        if (!empty($_REQUEST['RecordID']) && ($id = $_REQUEST['RecordID'])) {
                                $record = DataObject::get_by_id($className, $id);
                        }

                        //if no existing record, create new record
                        if (!$record) {
                                $record = new $className;
                        }

                        $form->saveInto($record);

                        try {
                                $record->write();
                        }
                        catch (Exception $e) {
                                $form->setMessage($e->getMessage(), 'bad');
                                return ContentModuleUtilities::json_response(array(
                                        "Status" => 0,
                                        "Message" => "{$className} saved successfully",
                                        "Content" => $form->forAjaxTemplate()
                                ));
                        }


                        $this->record->{$this->_relationship}()->add($record);

                        $form->loadDataFrom($record);

                        return ContentModuleUtilities::json_response(array(
                                "Status" => 1,
                                "Message" => "{$className} saved successfully",
                                "Content" => $this->ItemEditForm($record->ID)->forAjaxTemplate()->RAW()
                        ));

                }

                return ContentModuleUtilities::json_response(array(
                        "Status" => 0,
                        "Message" => "Error saving"
                ));

        }

	/**
	 * Set maximum items to display
	 * @param $bool
	 */
	public function setMaxItems($max_items) {
		$this->maxItems = $max_items;

		return $this;
	}

	public function getMaxItems() {
		return $this->maxItems;
	}

	public function getHasMaxItems(){
		if($this->maxItems != null && $this->getSourceCount() >= $this->maxItems)
			return true;
		else
			return false;
	}


}

class ContentModuleRelationshipEditor_Item extends ViewableData
{

        private $parent, $item, $_canEdit;

        public function __construct($parent, $item) {
                $this->parent = $parent;
                $this->item = $item;
        }

        public function Fields($xmlSafe = true) {
                $list = $this->parent->FieldList();
                foreach ($list as $fieldName => $fieldTitle) {
                        $value = "";

                        // This supports simple FieldName syntax
                        if (strpos($fieldName, '.') === false) {
                                $value = ($this->item->XML_val($fieldName) && $xmlSafe)
                                        ? $this->item->XML_val($fieldName)
                                        : $this->item->RAW_val($fieldName);
                                // This support the syntax fieldName = Relation.RelatedField
                        } else {
                                $fieldNameParts = explode('.', $fieldName);
                                $tmpItem = $this->item;
                                for ($j = 0; $j < sizeof($fieldNameParts); $j++) {
                                        $relationMethod = $fieldNameParts[$j];
                                        $idField = $relationMethod . 'ID';
                                        if ($j == sizeof($fieldNameParts) - 1) {
                                                if ($tmpItem) $value = $tmpItem->$relationMethod;
                                        } else {
                                                if ($tmpItem) $tmpItem = $tmpItem->$relationMethod();
                                        }
                                }
                        }


                        //escape
                        if ($escape = $this->parent->fieldEscape) {
                                foreach ($escape as $search => $replace) {
                                        $value = str_replace($search, $replace, $value);
                                }
                        }

                        $fields[] = new ArrayData(array(
                                "Name" => $fieldName,
                                "Title" => $fieldTitle,
                                "Value" => $value,
                        ));
                }
                return new ArrayList($fields);
        }

        public function getItem() {
                return $this->item;
        }

        public function setCanEdit($bool) {
                $this->_canEdit = $bool;
                return $this;
        }

        public function getCanEdit() {
                return $this->_canEdit;
        }
}
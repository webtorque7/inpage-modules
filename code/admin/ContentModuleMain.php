<?php

/**
 * The main "content" area of the CMS.
 *
 * This class creates a 2-frame layout - left-tree and right-form - to sit beneath the main
 * admin menu.
 *
 * @package inpage-modules
 */
class ContentModuleMain extends LeftAndMain implements PermissionProvider
{

	private static $url_segment = 'content-modules';

	private static $url_rule = '/$Action/$ID/$OtherID';

	// Maintain a lower priority than other administration sections
	// so that Director does not think they are actions of CMSMain
	private static $url_priority = 30;

	private static $menu_title = 'Edit Module';

	private static $menu_icon = 'inpage-modules/images/icon.png';

	private static $tree_class = 'ContentModule';

	private static $menu_priority = 50;

	private static $page_id = 0;

	private static $subitem_class = "Member";

	private static $allowed_actions = array(
		'deleteitems',
		'DeleteItemsForm',
		'dialog',
		'duplicate',
		'PublishItemsForm',
		'submit',
		'EditForm',
		'SearchForm',
		'batchactions',
		'listview',
		'ListViewForm',
		'publish',
		'delete'

	);

	public function init() {
		// set reading lang
		if (Object::has_extension('ContentModule', 'Translatable') && !$this->request->isAjax()) {
			Translatable::choose_site_locale(array_keys(Translatable::get_existing_content_languages('ContentModule')));
		}

		parent::init();


		Versioned::reading_stage("Stage");

		Requirements::css(CMS_DIR . '/css/screen.css');
		Requirements::css(INPAGE_MODULES_DIR . '/css/ContentModule_Admin.css');

		Requirements::combine_files(
			'contentmodulemain.js',
			array_merge(
				array(
					//INPAGE_MODULES_DIR . '/javascript/CMSMain.js',
					//INPAGE_MODULES_DIR . '/javascript/CMSMain.EditForm.js',
					//INPAGE_MODULES_DIR . '/javascript/CMSMain.AddForm.js',
					CMS_DIR . '/javascript/CMSPageHistoryController.js',
					//INPAGE_MODULES_DIR . '/javascript/SilverStripeNavigator.js',
				)
			//Requirements::add_i18n_javascript(INPAGE_MODULES_DIR . '/javascript/lang', true, true)
			)
		);

		//CMSBatchActionHandler::register('publish', 'CMSBatchAction_Publish');
		//CMSBatchActionHandler::register('unpublish', 'CMSBatchAction_Unpublish');
		//CMSBatchActionHandler::register('delete', 'CMSBatchAction_Delete');
		//CMSBatchActionHandler::register('deletefromlive', 'CMSBatchAction_DeleteFromLive');
		if (isset($_REQUEST['ID'])) {
			$this->setCurrentPageID($_REQUEST['ID']);
		}
	}

	public function index($request) {
		// In case we're not showing a specific record, explicitly remove any session state,
		// to avoid it being highlighted in the tree, and causing an edit form to show.
		if (!$request->param('Action')) {
			$this->setCurrentPageId(null);
		}

		return parent::index($request);
	}

	public function getResponseNegotiator() {
		$negotiator = parent::getResponseNegotiator();
		$controller = $this;
		$negotiator->setCallback(
			'ListViewForm',
			function () use (&$controller) {
				return $controller->ListViewForm()->forTemplate()->RAW();
			}
		);
		return $negotiator;
	}

	/**
	 * If this is set to true, the "switchView" context in the
	 * template is shown, with links to the staging and publish site.
	 *
	 * @return boolean
	 */
	public function ShowSwitchView() {
		return true;
	}

	/**
	 * Overloads the LeftAndMain::ShowView. Allows to pass a page as a parameter, so we are able
	 * to switch view also for archived versions.
	 */
	public function SwitchView($page = null) {
		if (!$page) {
			$page = $this->currentModule();
		}

		if ($page) {
			$nav = SilverStripeNavigator::get_for_record($page);
			return $nav['items'];
		}
	}

	//------------------------------------------------------------------------------------------//
	// Main controllers

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Override {@link LeftAndMain} Link to allow blank URL segment
	 *
	 * @return string
	 */
	public function Link($action = null) {
		$link = Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true), // in case we want to change the segment
			'/', // trailing slash needed if $action is null!
			"$action"
		);
		$this->extend('updateLink', $link);
		return $link;
	}

	public function LinkModules() {
		return singleton('ContentModulePagesController')->Link();
	}

	public function LinkModulesWithSearch() {
		return $this->LinkWithSearch($this->LinkModules());
	}

	public function LinkModuleEdit($id = null) {
		if (!$id) {
			$id = $this->currentPageID();
		}
		$base = singleton('ContentModuleEditController')->Link('show');
		$query = '';

		if (stripos($base, '?')) {
			$parts = explode('?', $base);
			$base = $parts[0];
			$query = $parts[1];
		}

		return $this->LinkWithSearch(
			Controller::join_links($base, $id), $query
		);
	}

	public function LinkModuleSettings() {
		if ($id = $this->currentPageID()) {
			return $this->LinkWithSearch(
				Controller::join_links(singleton('ContentModuleSettingsController')->Link('show'), $id)
			);
		}
	}

	public function LinkModuleHistory() {
		if ($id = $this->currentPageID()) {
			return $this->LinkWithSearch(
				Controller::join_links(singleton('ContentModuleHistoryController')->Link('show'), $id)
			);
		}
	}

	protected function LinkWithSearch($link, $query = '') {
		// Whitelist to avoid side effects
		$params = array(
			'q' => (array)$this->request->getVar('q'),
			'PageID' => $this->request->getVar('PageID')
		);

		//prepend ?/& to $query if needed
		if ($query && array_filter(array_values($params))) {
			$query = '&' . $query;
		} else if ($query) {
			$query = '?' . $query;
		}

		$link = Controller::join_links(
			$link,
			array_filter(array_values($params)) ? '?' . http_build_query($params) : null
		);

		$this->extend('updateLinkWithSearch', $link);

		return $link;
	}

	public function LinkModuleAdd($extraArguments = null) {
		$link = singleton("ContentModuleAddController")->Link();
		$this->extend('updateLinkModuleAdd', $link);
		if ($extraArguments) {
			$link = Controller::join_links($link, $extraArguments);
		}
		return $link;
	}

	/**
	 * Disable this for now, need to get it working
	 * @return string
	 */
	public function LinkPreview() {
		$record = $this->getRecord($this->currentPageID());
		$baseLink = ($record && $record instanceof Page) ? $record->Link('?stage=Stage') : Director::absoluteBaseURL();
		return false;
	}


	/**
	 * Returns a Form for page searching for use in templates.
	 *
	 * Can be modified from a decorator by a 'updateSearchForm' method
	 *
	 * @return Form
	 */
	public function SearchForm() {
		// Create the fields
		$content = new TextField('q[Title]', _t('CMSSearch.FILTERTITLEHEADING', 'Module Name'));
		$dateHeader = new HeaderField('q[Date]', _t('CMSSearch.FILTERDATEHEADING', 'Date'), 4);
		$dateFrom = new DateField(
			'q[LastEditedFrom]',
			_t('CMSSearch.FILTERDATEFROM', 'From')
		);
		$dateFrom->setConfig('showcalendar', true);
		$dateTo = new DateField(
			'q[LastEditedTo]',
			_t('CMSSearch.FILTERDATETO', 'To')
		);
		$dateTo->setConfig('showcalendar', true);
		$pageClasses = new DropdownField(
			'q[Module]',
			_t('ContentModule.MODULETYPEOPT', 'Module Type', 'Dropdown for limiting search to a module type'),
			$this->getModuleTypes()
		);
		$pageClasses->setEmptyString(_t('ContentModule.MODULETYPEANYOPT', 'Any'));

		// Group the Datefields
		$dateGroup = new FieldGroup(
			$dateHeader,
			$dateFrom,
			$dateTo
		);
		$dateGroup->setFieldHolderTemplate('FieldGroup_DefaultFieldHolder')->addExtraClass('stacked');

		// Create the Field list
		$fields = new FieldList(
			$content,
			$dateGroup,
			$pageClasses
		);

		// Create the Search and Reset action
		$actions = new FieldList(
			FormAction::create('doSearch', _t('CMSMain_left.ss.APPLY FILTER', 'Apply Filter'))
				->addExtraClass('ss-ui-action-constructive'),
			Object::create('ResetFormAction', 'clear', _t('CMSMain_left.ss.RESET', 'Reset'))
		);

		// Use <button> to allow full jQuery UI styling on the all of the Actions
		foreach ($actions->dataFields() as $action) {
			$action->setUseButtonTag(true);
		}

		// Create the form
		$form = Form::create($this, 'SearchForm', $fields, $actions)
			->addExtraClass('cms-search-form')
			->setFormMethod('GET')
			->setFormAction($this->Link())
			->disableSecurityToken()
			->unsetValidator();

		// Load the form with previously sent search data
		$form->loadDataFrom($this->request->getVars());

		// Allow decorators to modify the form
		$this->extend('updateSearchForm', $form);

		return $form;
	}

	/**
	 * Returns a sorted array suitable for a dropdown with moduletypes and their translated name
	 *
	 * @return array
	 */
	protected function getModuleTypes() {
		$pageTypes = array();
		foreach (ContentModule::content_module_types() as $pageTypeClass) {
			$pageTypes[$pageTypeClass->ClassName] = $pageTypeClass->i18n_singular_name();
		}
		ksort($pageTypes);
		return $pageTypes;
	}

	public function doSearch($data, $form) {
		return $this->getsubtree($this->request);
	}

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// The root element should point to the pages tree view,
		// rather than the actual controller (which would just show an empty edit form)
		$defaultTitle = self::menu_title_for_class('ContentModulesController');
		$items[0]->Title = _t("{$this->class}.MENUTITLE", $defaultTitle);
		$items[0]->Link = singleton('ContentModulesController')->Link();

		return $items;
	}


	/**
	 * Populates an array of classes in the CMS
	 * which allows the user to change the page type.
	 *
	 * @return SS_List
	 */
	public function ModuleTypesList() {
		$modules = ContentModule::content_module_types();

		$result = new ArrayList();

		foreach ($modules as $instance) {


			if (!$instance->canCreate()) {
				continue;
			}

			// skip this type if it is restricted
			if ($instance->stat('need_permission') && !$this->can($instance->stat('need_permission'))) {
				continue;
			}

			$addAction = $instance->i18n_singular_name();

			// Get description (convert 'Page' to 'SiteTree' for correct localization lookups)
			$description = _t($instance->class . '.DESCRIPTION');

			if (!$description) {
				$description = $instance->uninherited('description');
			}

			$instance->update(
				array(
					'ClassName' => $instance->class,
					'AddAction' => $addAction,
					'Description' => $description,
					// TODO Sprite support
					'IconURL' => $instance->stat('icon'),
					'Title' => $instance->i18n_singular_name(),
					'Total' => DataList::create($instance->class)->count(),
					'LastUpdate' => DataList::create($instance->class)->max('LastEdited')
				)
			);
			$result->push($instance);
		}

		$result = $result->sort('AddAction');

		return $result;
	}

	/**
	 * Get a database record to be managed by the CMS.
	 *
	 * @param int $id Record ID
	 * @param int $versionID optional Version id of the given record
	 */
	public function getRecord($id, $versionID = null) {

		$treeClass = $this->stat('tree_class');

		if ($id instanceof $treeClass) {
			return $id;
		} else if ($id && is_numeric($id)) {
			if ($this->request->getVar('Version')) {
				$versionID = (int)$this->request->getVar('Version');
			}

			if ($versionID) {
				$record = Versioned::get_version($treeClass, $id, $versionID);
			} else {
				$record = DataObject::get_one($treeClass, "\"$treeClass\".\"ID\" = $id");
			}

			// Then, try getting a record from the live site
			if (!$record) {
				// $record = Versioned::get_one_by_stage($treeClass, "Live", "\"$treeClass\".\"ID\" = $id");
				Versioned::reading_stage('Live');
				singleton($treeClass)->flushCache();

				$record = DataObject::get_one($treeClass, "\"$treeClass\".\"ID\" = $id");
				if ($record) {
					Versioned::set_reading_mode('');
				}
			}

			// Then, try getting a deleted record
			if (!$record) {
				$record = Versioned::get_latest_version($treeClass, $id);
			}

			// Don't open a page from a different locale
			/** The record's Locale is saved in database in 2.4, and not related with Session,
			 *  we should not check their locale matches the Translatable::get_current_locale,
			 *    here as long as we all the HTTPRequest is init with right locale.
			 *    This bit breaks the all FileIFrameField functions if the field is used in CMS
			 *  and its relevent ajax calles, like loading the tree dropdown for TreeSelectorField.
			 */
			/* if($record && Object::has_extension('SiteTree', 'Translatable') && $record->Locale && $record->Locale != Translatable::get_current_locale()) {
				$record = null;
			}*/

			return $record;

		} else if (substr($id, 0, 3) == 'new') {
			return $this->getNewItem($id);
		}
	}

	/**
	 * @param Int $id
	 * @param FieldList $fields
	 * @return Form
	 */
	public function getEditForm($id = null, $fields = null) {

		if (!$id) {
			$id = $this->currentPageID();
		}

		$form = parent::getEditForm($id);

		// TODO Duplicate record fetching (see parent implementation)
		$record = $this->getRecord($id);
		if ($record && !$record->canView()) {
			return Security::permissionFailure($this);
		}

		if (!$fields) {
			$fields = $form->Fields();
		}
		$actions = $form->Actions();

		if ($record) {
			$deletedFromStage = $record->IsDeletedFromStage;
			$deleteFromLive = !$record->ExistsOnLive;

			$fields->push($idField = new HiddenField("ID", false, $id));
			// Necessary for different subsites
			$fields->push($liveLinkField = new HiddenField("AbsoluteLink", false, $record->AbsoluteLink()));
			$fields->push($liveLinkField = new HiddenField("LiveLink"));
			$fields->push($stageLinkField = new HiddenField("StageLink"));

			if ($record->ID && is_numeric($record->ID)) {
				$liveLink = $record->getAbsoluteLiveLink();
				if ($liveLink) {
					$liveLinkField->setValue($liveLink);
				}
				if (!$deletedFromStage) {
					$stageLink = Controller::join_links($record->AbsoluteLink(), '?stage=Stage');
					if ($stageLink) {
						$stageLinkField->setValue($stageLink);
					}
				}
			}

			// Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
			/*if(in_array('CMSPreviewable', class_implements($record)) && !$fields->fieldByName('SilverStripeNavigator')) {
				$navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator());
				$navField->setAllowHTML(true);
				$fields->push($navField);
			}*/

			// getAllCMSActions can be used to completely redefine the action list
			if ($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = $record->getCMSActions();
			}


			// Use <button> to allow full jQuery UI styling
			$actionsFlattened = $actions->dataFields();
			if ($actionsFlattened) {
				foreach ($actionsFlattened as $action) {
					$action->setUseButtonTag(true);
				}
			}

			if ($record->hasMethod('getCMSValidator')) {
				$validator = $record->getCMSValidator();
			} else {
				$validator = new RequiredFields();
			}


			$form = new Form($this, "EditForm", $fields, $actions, $validator);

			$form->loadDataFrom($record);
			$form->disableDefaultAction();
			$form->addExtraClass('cms-edit-form content-module');
			$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
			// TODO Can't merge $FormAttributes in template at the moment
			$form->addExtraClass('center ' . $this->BaseCSSClasses());

			$form->setAttribute('data-pjax-fragment', 'CurrentForm');

			if (!$record->canEdit() || $deletedFromStage) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}

			$this->extend('updateEditForm', $form);
			return $form;
		} else if ($id) {

			return new Form(
				$this, "EditForm", new FieldList(
				new LabelField(
					'ModuleDoesntExistLabel',
					_t('ContentModule.MODULENOTEXISTS', "This module doesn't exist")
				)
			), new FieldList()
			);
		}

		return $this->ListViewForm();
	}

	public function listview($request) {
		return $this->renderWith($this->getTemplatesWithSuffix('_ListView'));
	}

	/**
	 * Returns the pages meet a certain criteria as {@see CMSSiteTreeFilter} or the subpages of a parent page
	 * defaulting to no filter and show all pages in first level.
	 * Doubles as search results, if any search parameters are set through {@link SearchForm()}.
	 *
	 * @param Array Search filter criteria
	 * @param Int Optional module parameter filters by the ClassName of the ContentModule
	 * @return SS_List
	 */
	public function getList($params, $module = '') {
		$list = new DataList($this->stat('tree_class'));
		$filter = null;
		$ids = array();
		if (isset($params['FilterClass']) && $filterClass = $params['FilterClass']) {
			if (!is_subclass_of($filterClass, 'CMSSiteTreeFilter')) {
				throw new Exception(sprintf('Invalid filter class passed: %s', $filterClass));
			}
			$filter = new $filterClass($params);
			$filterOn = true;
			foreach ($pages = $filter->pagesIncluded() as $pageMap) {
				$ids[] = $pageMap['ID'];
			}
			if (count($ids)) {
				$list = $list->where('"' . $this->stat('tree_class') . '"."ID" IN (' . implode(",", $ids) . ')');
			}
		} else if ($params) {
			$filter = array();
			if (!empty($params["Title"])) {
				$filter["Title:PartialMatch"] = $params["Title"];
			}
			if (!empty($params["LastEditedFrom"])) {
				$filter["LastEdited:GreaterThan"] = $params["LastEditedFrom"];
			}
			if (!empty($params["LastEditedTo"])) {
				$filter["LastEdited:LessThan"] = $params["LastEditedTo"];
			}
			if (!empty($params["Module"])) {
				$filter['ClassName'] = $params['Module'];
			}
			if (!empty($params['Locale'])) {
				$filter['Locale'] = $params['Locale'];
			}
			$list = $list->filter($filter);
		} else {
			$list = $list->filter("ClassName", $module ? $module : '');
		}

		return $list;
	}

	public function getModulesGridField($params, $module) {
		$list = $this->getList($params, $module);
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(30)
		);
		if ($module) {
			$gridFieldConfig->addComponent(
				GridFieldLevelup::create($module)
					->setLinkSpec('?Module=%s')
					->setAttributes(array('data-pjax' => 'ListViewForm,Breadcrumbs'))
			);
		}
		$gridField = new GridField('Module', 'Modules', $list, $gridFieldConfig);
		$columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');

		// Don't allow navigating into children nodes on filtered lists
		$fields = array(
			'Title' => _t('ContentModule.MODULETITLE', 'Module Title'),
			'Created' => _t('SiteTree.CREATED', 'Date Created'),
			'LastEdited' => _t('SiteTree.LASTUPDATED', 'Last Updated'),
		);
		$gridField->getConfig()->getComponentByType('GridFieldSortableHeader')->setFieldSorting(
			array('Title' => 'Title')
		);


		$columns->setDisplayFields($fields);
		$columns->setFieldCasting(
			array(
				'Created' => 'Datetime->Ago',
				'LastEdited' => 'Datetime->Ago',
				'Title' => 'HTMLText'
			)
		);

		$controller = $this;

		$columns->setFieldFormatting(
			array(
				'Title' => function ($value, &$item) use ($controller) {
					return '<a class="action-detail" href="' . $controller->LinkModuleEdit($item->ID) . '">' . $item->Title . '</a>';
				}
			)
		);

		return $gridField;
	}

	public function getModuleTypesGridField() {
		$list = $this->ModuleTypesList();
		$gridFieldConfig = GridFieldConfig::create()->addComponents(
			new GridFieldSortableHeader(),
			new GridFieldDataColumns(),
			new GridFieldPaginator(30)
		);

		$gridField = new GridField('ModuleTypes', 'Module Types', $list, $gridFieldConfig);
		$columns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');

		// Don't allow navigating into children nodes on filtered lists
		$fields = array(
			'Title' => _t('ContentModule.MODULETYPE', 'Module Type'),
			'Total' => _t('ContentModule.MODULETYPETOTAL', 'Total'),
			'LastUpdate' => _t('ContentModule.MODULETYPELASTUPDATE', 'Last Update'),
		);

		$gridField->getConfig()->getComponentByType('GridFieldSortableHeader')->setFieldSorting(
			array('Title' => 'Title')
		);


		//$fields = array_merge(array('listChildrenLink' => ''), $fields);


		$columns->setDisplayFields($fields);
		$columns->setFieldCasting(
			array(
				'Title' => 'HTMLText',
				'LastUpdate' => 'SS_Datetime'
			)
		);

		$controller = $this;
		$columns->setFieldFormatting(
			array(
				/*'listChildrenLink' => function($value, &$item) use($controller) {

								return sprintf(
										'<a class="cms-panel-link list-children-link" data-pjax-target="ListViewForm,Breadcrumbs" href="%s">&gt;</a>',
										Controller::join_links($controller->Link() . "?Module={$item->ClassName}")
								);
				},*/
				'Title' => function ($value, &$item) use ($controller) {
					return $item->Total ? sprintf(
						'<a class="action-detail" href="%s">%s</a>',
						Controller::join_links($controller->Link(), '?Module=' . $item->ClassName),
						$item->Title
					) : $item->Title;
				}
			)
		);

		return $gridField;
	}

	public function ListViewForm() {
		$params = $this->request->requestVar('q');

		if (($module = $this->request->requestVar('Module')) || (!empty($params))) {
			$gridField = $this->getModulesGridField($params, $module);
		} else {
			$gridField = $this->getModuleTypesGridField();
		}

		$listview = new Form(
			$this,
			'ListViewForm',
			new FieldList($gridField),
			new FieldList()
		);

		//$listview->addExtraClass('cms-edit-form');
		$listview->setTemplate($this->getTemplatesWithSuffix('_ListViewForm'));
		// TODO Can't merge $FormAttributes in template at the moment
		$listview->addExtraClass($this->BaseCSSClasses());

		$listview->setAttribute('data-pjax-fragment', 'ListViewForm');

		$this->extend('updateListView', $listview);

		$listview->disableSecurityToken();
		return $listview;
	}

	/*public function currentPageID() {
		$id = self::$page_id;

                //fallback to first
                //if ($module = ContentModule::get()->first()) {
                 //       $id = $module->ID;
                //}

		return $id;
	}

        public function setCurrentPageID($id) {
                self::$page_id = $id;
                return $this;
        }*/

	//------------------------------------------------------------------------------------------//
	// Data saving handlers

	/**
	 * Save and Publish page handler
	 */
	public function save($data, $form) {
		$className = $this->stat('tree_class');

		// Existing or new record?
		$SQL_id = Convert::raw2sql($data['ID']);
		if (substr($SQL_id, 0, 3) != 'new') {
			$record = DataObject::get_by_id($className, $SQL_id);
			if ($record && !$record->canEdit()) {
				return Security::permissionFailure($this);
			}
			if (!$record || !$record->ID) {
				throw new SS_HTTPResponse_Exception("Bad record ID #$SQL_id", 404);
			}
		} else {
			if (!singleton($this->stat('tree_class'))->canCreate()) {
				return Security::permissionFailure($this);
			}
			$record = $this->getNewItem($SQL_id, false);
		}

		// Update the class instance if necessary
		if (isset($data['ClassName']) && $data['ClassName'] != $record->ClassName) {
			$newClassName = $record->ClassName;
			// The records originally saved attribute was overwritten by $form->saveInto($record) before.
			// This is necessary for newClassInstance() to work as expected, and trigger change detection
			// on the ClassName attribute
			$record->setClassName($data['ClassName']);
			// Replace $record with a new instance
			$record = $record->newClassInstance($newClassName);
		}

		// save form data into record
		$form->saveInto($record);

		$record->write();

		// If the 'Save & Publish' button was clicked, also publish the page
		if (isset($data['publish']) && $data['publish'] == 1) {

			$response = $record->doPublish();

			$this->response->addHeader(
				'X-Status',
				rawurlencode(
					_t(
						'LeftAndMain.STATUSPUBLISHEDSUCCESS',
						"Published '{title}' successfully",
						'Status message after publishing a module, showing the module title',
						array('title' => $record->Title)
					)
				)
			);
		} else {
			$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP', 'Saved.')));
		}

		return $this->getResponseNegotiator()->respond($this->request);
	}

	/**
	 * @uses LeftAndMainExtension->augmentNewModuleItem()
	 */
	public function getNewItem($id, $setID = true) {
		list($dummy, $className, $suffix) = array_pad(explode('-', $id), 3, null);

		$newItem = new $className();

		$newItem->ClassName = $className;

		if ($setID) {
			$newItem->ID = $id;
		}

		# Some modules like subsites add extra fields that need to be set when the new item is created
		$this->extend('augmentNewModuleItem', $newItem);

		return $newItem;
	}

	/**
	 * Delete the page from live. This means a page in draft mode might still exist.
	 *
	 * @see delete()
	 */
	public function deletefromlive($data, $form) {
		Versioned::reading_stage('Live');
		$record = DataObject::get_by_id("SiteTree", $data['ID']);
		if ($record && !($record->canDelete() && $record->canDeleteFromLive())) {
			return Security::permissionFailure($this);
		}

		$descRemoved = '';
		$descendantsRemoved = 0;
		$recordTitle = $record->Title;
		$recordID = $record->ID;

		Versioned::reading_stage('Stage');

		$this->response->addHeader(
			'X-Status',
			rawurlencode(
				_t(
					'CMSMain.REMOVED',
					'Deleted \'{title}\'{description} from live site',
					array('title' => $recordTitle, 'description' => $descRemoved)
				)
			)
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->getResponseNegotiator()->respond($this->request);
	}

	/**
	 * Actually perform the publication step
	 */
	public function performPublish($record) {
		if ($record && !$record->canPublish()) {
			return Security::permissionFailure($this);
		}

		$record->doPublish();
	}

	public function canView($member = null) {
		return true;
	}

	/**
	 * Reverts a page by publishing it to live.
	 * Use {@link restorepage()} if you want to restore a page
	 * which was deleted from draft without publishing.
	 *
	 * @uses SiteTree->doRevertToLive()
	 */
	public function revert($data, $form) {
		if (!isset($data['ID'])) {
			return new SS_HTTPResponse("Please pass an ID in the form content", 400);
		}

		$id = (int)$data['ID'];
		$restoredPage = Versioned::get_latest_version("ContentModule", $id);
		if (!$restoredPage) {
			return new SS_HTTPResponse("ContentModule #$id not found", 400);
		}

		$record = Versioned::get_one_by_stage(
			'SiteTree',
			'Live',
			sprintf("\"SiteTree_Live\".\"ID\" = '%d'", (int)$data['ID'])
		);

		// a user can restore a page without publication rights, as it just adds a new draft state
		// (this action should just be available when page has been "deleted from draft")
		if ($record && !$record->canEdit()) {
			return Security::permissionFailure($this);
		}
		if (!$record || !$record->ID) {
			throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
		}

		$record->doRevertToLive();

		$this->response->addHeader(
			'X-Status',
			rawurlencode(
				_t(
					'CMSMain.RESTORED',
					"Restored '{title}' successfully",
					'Param %s is a title',
					array('title' => $record->Title)
				)
			)
		);

		return $this->getResponseNegotiator()->respond($this->request);
	}

	/**
	 * Delete the current page from draft stage.
	 * @see deletefromlive()
	 */
	public function delete($data, $form) {
		$id = Convert::raw2sql($data['ID']);
		$record = DataObject::get_one(
			"ContentModule",
			sprintf("\"ContentModule\".\"ID\" = %d", $id)
		);
		if ($record && !$record->canDelete()) {
			return Security::permissionFailure();
		}
		if (!$record || !$record->ID) {
			throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
		}

		// save ID and delete record
		$recordID = $record->ID;
		$record->delete();

		$this->response->addHeader(
			'X-Status',
			rawurlencode(
				sprintf(_t('CMSMain.REMOVEDPAGEFROMDRAFT', "Removed '%s' from the draft site"), $record->Title)
			)
		);

		// Even if the record has been deleted from stage and live, it can be viewed in "archive mode"
		return $this->getResponseNegotiator()->respond($this->request);
	}

	public function publish($data, $form) {
		$data['publish'] = '1';

		return $this->save($data, $form);
	}

	public function unpublish($data, $form) {
		$className = $this->stat('tree_class');
		$record = DataObject::get_by_id($className, $data['ID']);

		if ($record && !$record->canDeleteFromLive()) {
			return Security::permissionFailure($this);
		}
		if (!$record || !$record->ID) {
			throw new SS_HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
		}

		$record->doUnpublish();

		$this->response->addHeader(
			'X-Status',
			rawurlencode(
				_t('CMSMain.REMOVEDPAGE', "Removed '{title}' from the published site", array('title' => $record->Title))
			)
		);

		return $this->getResponseNegotiator()->respond($this->request);
	}

	/**
	 * @return array
	 */
	public function rollback() {
		return $this->doRollback(
			array(
				'ID' => $this->currentPageID(),
				'Version' => $this->request->param('VersionID')
			),
			null
		);
	}

	/**
	 * Rolls a site back to a given version ID
	 *
	 * @param array
	 * @param Form
	 *
	 * @return html
	 */
	public function doRollback($data, $form) {
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
				'CMSMain.ROLLEDBACKVERSION',
				"Rolled back to version #%d.  New version number is #%d",
				array('version' => $data['Version'], 'versionnew' => $record->Version)
			);
		} else {
			$record->doRollbackTo('Live');
			$message = _t(
				'CMSMain.ROLLEDBACKPUB',
				"Rolled back to published version. New version number is #{version}",
				array('version' => $record->Version)
			);
		}

		$this->response->addHeader('X-Status', rawurlencode($message));

		// Can be used in different contexts: In normal page edit view, in which case the redirect won't have any effect.
		// Or in history view, in which case a revert causes the CMS to re-load the edit view.
		// The X-Pjax header forces a "full" content refresh on redirect.
		$url = Controller::join_links(singleton('CMSPageEditController')->Link('show'), $record->ID);
		$this->response->addHeader('X-ControllerURL', $url);
		$this->request->addHeader('X-Pjax', 'Content');
		$this->response->addHeader('X-Pjax', 'Content');

		return $this->getResponseNegotiator()->respond($this->request);
	}

	/**
	 * Batch Actions Handler
	 */
	public function batchactions() {
		return new CMSBatchActionHandler($this, 'batchactions');
	}

	public function BatchActionParameters() {
		$batchActions = CMSBatchActionHandler::$batch_actions;

		$forms = array();
		foreach ($batchActions as $urlSegment => $batchAction) {
			$SNG_action = singleton($batchAction);
			if ($SNG_action->canView() && $fieldset = $SNG_action->getParameterFields()) {
				$formHtml = '';
				foreach ($fieldset as $field) {
					$formHtml .= $field->Field();
				}
				$forms[$urlSegment] = $formHtml;
			}
		}
		$pageHtml = '';
		foreach ($forms as $urlSegment => $html) {
			$pageHtml .= "<div class=\"params\" id=\"BatchActionParameters_$urlSegment\">$html</div>\n\n";
		}
		return new LiteralField(
			"BatchActionParameters",
			'<div id="BatchActionParameters" style="display:none">' . $pageHtml . '</div>'
		);
	}

	/**
	 * Returns a list of batch actions
	 */
	public function BatchActionList() {
		return $this->batchactions()->batchActionList();
	}

	public function buildbrokenlinks($request) {
		// Protect against CSRF on destructive action
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}

		increase_time_limit_to();
		increase_memory_limit_to();

		if ($this->urlParams['ID']) {
			$newPageSet[] = DataObject::get_by_id("Page", $this->urlParams['ID']);
		} else {
			$pages = DataObject::get("Page");
			foreach ($pages as $page) {
				$newPageSet[] = $page;
			}
			$pages = null;
		}

		$content = new HtmlEditorField('Content');
		$download = new HtmlEditorField('Download');

		foreach ($newPageSet as $i => $page) {
			$page->HasBrokenLink = 0;
			$page->HasBrokenFile = 0;

			$content->setValue($page->Content);
			$content->saveInto($page);

			$download->setValue($page->Download);
			$download->saveInto($page);

			echo "<li>$page->Title (link:$page->HasBrokenLink, file:$page->HasBrokenFile)";

			$page->writeWithoutVersion();
			$page->destroy();
			$newPageSet[$i] = null;
		}
	}

	public function publishall($request) {
		if (!Permission::check('ADMIN')) {
			return Security::permissionFailure($this);
		}

		increase_time_limit_to();
		increase_memory_limit_to();

		$response = "";

		if (isset($this->requestParams['confirm'])) {
			// Protect against CSRF on destructive action
			if (!SecurityToken::inst()->checkRequest($request)) {
				return $this->httpError(400);
			}

			$start = 0;
			$pages = DataObject::get("SiteTree", "", "", "", "$start,30");
			$count = 0;
			while ($pages) {
				foreach ($pages as $page) {
					if ($page && !$page->canPublish()) {
						return Security::permissionFailure($this);
					}

					$page->doPublish();
					$page->destroy();
					unset($page);
					$count++;
					$response .= "<li>$count</li>";
				}
				if ($pages->Count() > 29) {
					$start += 30;
					$pages = DataObject::get("SiteTree", "", "", "", "$start,30");
				} else {
					break;
				}
			}
			$response .= _t('CMSMain.PUBPAGES', "Done: Published {count} pages", array('count' => $count));

		} else {
			$token = SecurityToken::inst();
			$fields = new FieldList();
			$token->updateFieldSet($fields);
			$tokenField = $fields->First();
			$tokenHtml = ($tokenField) ? $tokenField->FieldHolder() : '';
			$response .= '<h1>' . _t('CMSMain.PUBALLFUN', '"Publish All" functionality') . '</h1>
				<p>' . _t(
					'CMSMain.PUBALLFUN2',
					'Pressing this button will do the equivalent of going to every page and pressing "publish".  It\'s
				intended to be used after there have been massive edits of the content, such as when the site was
				first built.'
				) . '</p>
				<form method="post" action="publishall">
					<input type="submit" name="confirm" value="'
				. _t(
					'CMSMain.PUBALLCONFIRM',
					"Please publish every page in the site, copying content stage to live",
					'Confirmation button'
				) . '" />'
				. $tokenHtml .
				'</form>';
		}

		return $response;
	}

	/**
	 * Restore a completely deleted page from the SiteTree_versions table.
	 */
	public function restore($data, $form) {
		if (!isset($data['ID']) || !is_numeric($data['ID'])) {
			return new SS_HTTPResponse("Please pass an ID in the form content", 400);
		}

		$id = (int)$data['ID'];
		$restoredPage = Versioned::get_latest_version("ContentModule", $id);
		if (!$restoredPage) {
			return new SS_HTTPResponse("ContentModule #$id not found", 400);
		}

		$restoredPage = $restoredPage->doRestoreToStage();

		$this->response->addHeader(
			'X-Status',
			rawurlencode(
				_t(
					'CMSMain.RESTORED',
					"Restored '{title}' successfully",
					array('title' => $restoredPage->Title)
				)
			)
		);

		return $this->getResponseNegotiator()->respond($this->request);
	}

	public function duplicate($request) {
		// Protect against CSRF on destructive action
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}

		if (($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if ($page && (!$page->canEdit() || !$page->canCreate())) {
				return Security::permissionFailure($this);
			}
			if (!$page || !$page->ID) {
				throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
			}

			$newPage = $page->duplicate();

			// ParentID can be hard-set in the URL.  This is useful for pages with multiple parents
			if ($_GET['parentID'] && is_numeric($_GET['parentID'])) {
				$newPage->ParentID = $_GET['parentID'];
				$newPage->write();
			}

			// Reload form, data and actions might have changed
			$form = $this->getEditForm($newPage->ID);

			return $form->forTemplate()->RAW();
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}

	public function duplicatewithchildren($request) {
		// Protect against CSRF on destructive action
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}

		if (($id = $this->urlParams['ID']) && is_numeric($id)) {
			$page = DataObject::get_by_id("SiteTree", $id);
			if ($page && (!$page->canEdit() || !$page->canCreate())) {
				return Security::permissionFailure($this);
			}
			if (!$page || !$page->ID) {
				throw new SS_HTTPResponse_Exception("Bad record ID #$id", 404);
			}

			$newPage = $page->duplicateWithChildren();

			// Reload form, data and actions might have changed
			$form = $this->getEditForm($newPage->ID);

			return $form->forTemplate()->RAW();
		} else {
			user_error("CMSMain::duplicate() Bad ID: '$id'", E_USER_WARNING);
		}
	}

	/**
	 * Return the version number of this application.
	 * Uses the subversion path information in <mymodule>/silverstripe_version
	 * (automacially replaced by build scripts).
	 *
	 * @return string
	 */
	public function CMSVersion() {
		$cmsVersion = file_get_contents(CMS_PATH . '/silverstripe_version');
		if (!$cmsVersion) {
			$cmsVersion = _t('LeftAndMain.VersionUnknown', 'Unknown');
		}

		$frameworkVersion = file_get_contents(FRAMEWORK_PATH . '/silverstripe_version');
		if (!$frameworkVersion) {
			$frameworkVersion = _t('LeftAndMain.VersionUnknown', 'Unknown');
		}

		return sprintf(
			"CMS: %s Framework: %s",
			$cmsVersion,
			$frameworkVersion
		);
	}

	public function providePermissions() {
		$title = _t("CMSPagesController.MENUTITLE", LeftAndMain::menu_title_for_class('CMSPagesController'));
		return array(
			"CMS_ACCESS_CMSMain" => array(
				'name' => _t('CMSMain.ACCESS', "Access to '{title}' section", array('title' => $title)),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t(
					'CMSMain.ACCESS_HELP',
					'Allow viewing of the section containing page tree and content. View and edit permissions can be handled through page specific dropdowns, as well as the separate "Content permissions".'
				),
				'sort' => -99 // below "CMS_ACCESS_LeftAndMain", but above everything else
			)
		);
	}

	/**
	 * Populates an array of classes in the CMS
	 * which allows the user to change the page type.
	 *
	 * @return SS_List
	 */
	public function ModuleTypes() {
		$classes = ContentModule::content_module_types();

		$result = new ArrayList();

		foreach ($classes as $instance) {

			$class = $instance->class;

			if ($instance instanceof HiddenClass) {
				continue;
			}

			if (!$instance->canCreate()) {
				continue;
			}

			// skip this type if it is restricted
			if ($instance->stat('need_permission') && !$this->can(singleton($class)->stat('need_permission'))) {
				continue;
			}

			$addAction = $instance->i18n_singular_name();

			// Get description (convert 'Page' to 'SiteTree' for correct localization lookups)
			$description = _t($class . '.DESCRIPTION');

			if (!$description) {
				$description = $instance->uninherited('description');
			}

			$result->push(
				new ArrayData(
					array(
						'ClassName' => $class,
						'AddAction' => $addAction,
						'Description' => $description,
						// TODO Sprite support
						'IconURL' => $instance->stat('icon'),
						'Title' => singleton($class)->i18n_singular_name(),
					)
				)
			);
		}

		$result = $result->sort('AddAction');

		return $result;
	}

}

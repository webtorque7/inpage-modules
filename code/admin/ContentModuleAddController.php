<?php
class ContentModuleAddController extends ContentModuleEditController {

	private static $url_segment = 'content-modules/add';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $url_priority = 42;
	private static $menu_title = 'Add page';
	private static $required_permission_codes = 'CMS_ACCESS_CMSMain';

	private static $allowed_actions = array(
		'AddForm',
		'doAdd',
	);

	/**
	 * @return Form
	 */
	public function AddForm() {
		$record = $this->currentPage();
		
		$moduleTypes = array();
		foreach($this->ModuleTypes() as $type) {
			$html = sprintf('<span class="page-icon class-%s"></span><strong class="title">%s</strong><span class="description">%s</span>',
				$type->getField('Title'),
				$type->getField('AddAction'),
				$type->getField('Description')
			);
			$moduleTypes[$type->getField('ClassName')] = $html;
		}
		// Ensure generic page type shows on top
		if(isset($moduleTypes['Page'])) {
			$pageTitle = $moduleTypes['Page'];
			$moduleTypes = array_merge(array('Page' => $pageTitle), $moduleTypes);
		}

		$numericLabelTmpl = '<span class="step-label"><span class="flyout">%d</span><span class="arrow"></span><span class="title">%s</span></span>';

		$topTitle = _t('CMSPageAddController.ParentMode_top', 'Top level');
		$childTitle = _t('CMSPageAddController.ParentMode_child', 'Under another page');

		$fields = new FieldList(
			// new HiddenField("ParentID", false, ($this->parentRecord) ? $this->parentRecord->ID : null),
			// TODO Should be part of the form attribute, but not possible in current form API

			$typeField = new OptionsetField(
				"ModuleType",
				sprintf($numericLabelTmpl, 1, _t('ContentModuleMain.ChooseModuleType', 'Choose module type')),
				$moduleTypes
			)
		);
		
		$actions = new FieldList(
			// $resetAction = new ResetFormAction('doCancel', _t('CMSMain.Cancel', 'Cancel')),
			FormAction::create("doAdd", _t('CMSMain.Create',"Create"))
				->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
				->setUseButtonTag(true)
		);
		
		$this->extend('updateModuleOptions', $fields);
		
		$form = new Form($this, "AddForm", $fields, $actions);
		$form->addExtraClass('cms-add-form stacked cms-content center cms-edit-form ' . $this->BaseCSSClasses());
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		if($parentID = $this->request->getVar('ParentID')) {
			$form->Fields()->dataFieldByName('ParentID')->setValue((int)$parentID);
		}

		return $form;
	}

	public function doAdd($data, $form) {
		$className = isset($data['ModuleType']) ? $data['ModuleType'] : "TextModule";

		$record = Injector::inst()->get($className);
		if(class_exists('Translatable') && $record->hasExtension('Translatable') && isset($data['Locale'])) {
			$record->Locale = $data['Locale'];
		}

		try {
			$record->write();
			$record->writeToStage('Stage');
		} catch(ValidationException $ex) {
			$form->sessionMessage($ex->getResult()->message(), 'bad');
			return $this->getResponseNegotiator()->respond($this->request);
		}

		$editController = singleton('ContentModuleEditController');
		$editController->setCurrentPageID($record->ID);

		Session::set(
			"FormInfo.Form_EditForm.formError.message", 
			_t('CMSMain.PageAdded', 'Successfully created module')
		);
		Session::set("FormInfo.Form_EditForm.formError.type", 'good');
		
		return $this->redirect(Controller::join_links($editController->Link('show'), $record->ID));
	}

}

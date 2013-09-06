<?php

/**
 * Module for displaying 3 columns of text
 *
 * @package inpage-modules
 */
class TextColumnsModule extends ContentModule {

	public static $singular_name = '3 Column Text';
	public static $plural_name = '3 Column Text';

        public static $db = array(
                'ContentTitle' => 'Varchar(255)',
        );

        public static $has_many = array(
                'Columns' => 'TextColumn'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new TextField('ContentTitle', 'Content Title'),
                        ContentModuleRelationshipEditor::create('Columns', 'Columns', 'Columns', $this)->setShowAddButton(true)->setSortField('Sort'),
                ));

                return $fields;
        }
}

/**
 * Columns for TextColumnsModule {@link TextColumnsModule}
 * @package inpage-modules
 */
class TextColumn extends DataObject
{
        public static $db = array(
                'Title' => 'Varchar',
                'Text' => 'Text',
                'Sort' => 'Int'
        );

        public static $has_one = array(
                'TextColumnsModule' => 'TextColumnsModule',
		'Link' => 'Page'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->removeByName('TextColumnsModuleID');
                $fields->removeByName('Sort');
		$fields->addFieldToTab('Root.Main', OptionalTreeDropdownField::create('LinkID', 'Link (optional)', 'SiteTree')->setEmptyString('No link'));

                return $fields;
        }

	public function canEdit($member = null) {
		return true;
	}

	public function canCreate($member = null) {
		return true;
	}

	public function canDelete($member = null) {
		return true;
	}
}
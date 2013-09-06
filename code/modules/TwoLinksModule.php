<?php
/**
 * A module for displaying two columns with text and link
 * @package inpage-modules
 */
class TwoLinksModule extends ContentModule
{
        public static $singular_name = '2 Column Text';
        public static $plural_name = '2 Column Text';

        public static $db = array(
                'ContentTitle' => 'Text',
        );

        public static $has_many = array(
                'Links' => 'TwoLinksLink'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new TextField('ContentTitle', 'Content Title'),
                        $editor = new ContentModuleRelationshipEditor('Links', 'Links', 'Links', $this)
                ));

                $editor->setShowAddButton(true);

                return $fields;
        }
}

/**
 * Columns for the Two Links Module {@TwoLinksModule}
 * @package inpage-modules
 */
class TwoLinksLink extends DataObject
{
        public static $db = array(
                'Title' => 'Varchar',
                'Text' => 'Text'
        );

        public static $has_one = array(
                'Page' => 'Page',
                'TwoLinksModule' => 'TwoLinksModule'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->removeByName('TwoLinksModuleID');
                $fields->addFieldToTab('Root.Main', TreeDropdownField::create('PageID', 'Page', 'SiteTree'));

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
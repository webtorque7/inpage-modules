<?php
/**
 * Add many benefits with image, title and text
 * @package inpage-modules
 */
class BenefitsModule extends ContentModule {

	public static $singular_name = '4 Column Text';
	public static $plural_name = '4 Column Text';

        public static $benefits_limit = 4;

        public static $db = array(
                'ContentTitle' => 'Text'
        );

        public static $many_many = array(
                'Benefits' => 'BenefitItem'
        );

        public static $many_many_extraFields = array(
                'Benefits' => array(
                        'SortOrder' => 'Int(0)'
                )
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new TextField('ContentTitle', 'Content Title'),
                        $benefits = new ContentModuleRelationshipEditor('Benefits', 'Benefits', 'Benefits', $this)
                ));

                $benefits->setShowAddButton(true)
                        ->setShowAddExistingButton(true)
                        ->setSortField('SortOrder')
			->setMaxItems(self::$benefits_limit);

                return $fields;
        }

        public function SortedBenefits() {
                return $this->getManyManyComponents('Benefits')->sort('SortOrder')->limit(self::$benefits_limit);
        }

        public function Benefits() {
                return $this->SortedBenefits();
        }
}

/**
 * Individual Benefit Items
 *
 * @package inpage-modules
 */
class BenefitItem extends DataObject
{
        public static $singular_name = 'Benefit Item';
        public static $plural_name = 'Benefit Items';

        public static $db = array(
                'Title' => 'Varchar',
                'Text' => 'Text'
        );

        //public static $default_sort = '"SortOrder" ASC';

        public static $has_one = array(
                'Image' => 'Image'
        );

        public static $summary_fields = array(
                'Title' => 'Title',
                'CMSThumbnail' => 'Thumbnail'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                if ($upload = $fields->dataFieldByName('Image')) {
                        $upload->setFolderName('Benefits');
                }

                return $fields;
        }

        public function CMSThumbnail() {
                if ($this->Image() && $this->Image()->exists()) {
                        return $this->Image()->CMSThumbnail();
                }
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
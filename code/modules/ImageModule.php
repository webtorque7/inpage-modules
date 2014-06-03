<?php
/**
 * Module for displaying images, multiple images creates a slideshow
 * @package inpage-modules
 */

class ImageModule extends ContentModule
{

	private static $singular_name = 'Image';
	private static $plural_name = 'Image';

	private static $db = array(
		'ContentTitle' => 'Varchar(150)',
                'ResizeMethod' => 'Enum("Cropped, SetWidth, SetHeight", "Cropped")',
                'ResizeWidth' => 'Int',
                'ResizeHeight' => 'Int'
        );

	private static $many_many = array(
                'Images' => 'Image',
        );

	private static $many_many_extraFields = array(
                'Images' => array(
                        'SortOrder' => 'Int'
                )
        );

	private static $default_width = 960;
	private static $default_height = 300;

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
			new TextField('ContentTitle', 'Content Title'),
			new DropdownField('ResizeMethod', 'Resize Method', $this->dbObject('ResizeMethod')->enumValues()),
			new NumericField('ResizeWidth', 'Resize Width'),
			new NumericField('ResizeHeight', 'Resize Height'),
			new ContentModuleUploadField('Images'),
                	new ContentModuleRelationshipEditor('ImagesRelationEditor', 'Images', 'Images', $this, array(
                        	'Title' => 'Title',
                        	'CMSThumbnail' => 'Thumbnail'
			))
		));

                return $fields;
        }

        public function SortedImages() {
                return $this->Images()->sort('"SortOrder" ASC');
        }

        public function ResizedImages($method = null, $width = null, $height = null) {
                $images = $this->SortedImages();

                $return = new ArrayList();

                if ($images) foreach ($images as $image) {
                        $return->push($this->resizeImage($image, $method, $width, $height));
                }

                return $return;
        }

        public function resizeImage(Image $image, $method = null, $width = null, $height = null) {
                $method = $method ? $method : $this->ResizeMethod;
                $width = $width ? $width : $this->ResizeWidth;
                $height = $height ? $height : $this->ResizeHeight;

		if (!$width) $width = self::$default_width;
		if (!$height) $height = self::$default_height;

                switch ($method) {
                        case 'SetWidth' :
                                return $image->SetWidth($width);
                        case 'SetHeight' :
                                return $image->SetHeight($height);
                        case 'Cropped' :
                                default:
                                return $image->CroppedImage($width, $height);
                }
        }

        /**
         * Set form to prevent error with link
         * @return Object
         */
        public function uploadField() {
                $field = new UploadField('Images');
                $field->setRecord($this);
                if (ContentModuleField::curr()) {
                        $field->setForm(ContentModuleField::curr()->getForm());
                }
                return $field;
        }

        public function onBeforeWrite() {
                parent::onBeforeWrite();

                if (!$this->ResizeWidth) $this->ResizeWidth = self::$default_width;
                if (!$this->ResizeHeight) $this->ResizeHeight = self::$default_height;
        }
}
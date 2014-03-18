<?php

/**
 * A module for displaying text, if summary, main text is hidden until expanded
 *
 * @package inpage-modules
 */
class TextModule extends ContentModule {

	private static $singular_name = 'Text';
	private static $plural_name = 'Text';

	private static $db = array(
                'ContentTitle' => 'Text',
                'Text' => 'HTMLText'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new TextField('ContentTitle', 'Content Title'),
                        new HtmlEditorField('Text')
                ));

                return $fields;
        }
}
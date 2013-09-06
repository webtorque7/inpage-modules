<?php

/**
 * A module for displaying text, if summary, main text is hidden until expanded
 *
 * @package inpage-modules
 */
class TextModule extends ContentModule {

        public static $singular_name = 'Text';
        public static $plural_name = 'Text';

        public static $db = array(
                'ContentTitle' => 'Text',
                'Summary' => 'HTMLText',
                'Text' => 'HTMLText'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new TextField('ContentTitle', 'Content Title'),
                        $summary = new HtmlEditorField('Summary'),
                        new HtmlEditorField('Text', 'Read More Text')
                ));

                $summary->setRows(5);

                return $fields;
        }
}
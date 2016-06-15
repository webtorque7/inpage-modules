<?php

/**
 * A module for displaying text, if summary, main text is hidden until expanded
 *
 * @package inpage-modules
 */
class TextModule extends ContentModule
{

    private static $singular_name = 'Text';
    private static $plural_name = 'Text';

    private static $db = array(
                'ContentTitle' => 'Text',
                'Text' => 'HTMLText'
        );

    public function getCMSFields()
    {
        $fields = new FieldList(
            $rootTab = new TabSet("Root",
                $tabMain = new Tab('Main',
                    new TextField('ContentTitle', 'Content Title'),
                    new HtmlEditorField('Text')
                )
            )
        );

        $this->extend('updateCMSFields', $fields);
        return $fields;
    }
}

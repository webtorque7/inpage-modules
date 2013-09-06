<?php
/**
 * Module for having Image on the left, log and title on right, and text below
 *
 * @package inpage-modules
 */
class CaseStudyModule extends ContentModule {

	public static $singular_name = '60 Text/40 Image';
	public static $plural_name = '60 Text/40 Image';

        public static $db = array(
		'CaseStudyText' => 'Text',
        );

        public static $has_one = array(
                'CaseStudy' => 'CaseStudy',
                'Logo' => 'Image'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new DropdownField('CaseStudyID', 'Case Study', CaseStudy::get()->sort('"Title" ASC')->map()),
                        //new ContentModuleUploadField('Logo')
                ));

                return $fields;
        }

	public function getCaseStudyText() {
		return $this->getField('CaseStudyText') ? $this->getField('CaseStudyText') : $this->CaseStudy()->getTileSummaryText();
	}
}
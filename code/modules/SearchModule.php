<?php
/**
 * A module for searching content of the site
 *
 * @package inpage-modules
 */

class SearchModule extends ContentModule {

	public static $singular_name = 'Product Search';
	public static $plural_name = 'Product Search';

        public static $db = array(
                'ContentTitle' => 'Varchar',
                'Text' => 'Text',
                'SearchTitle' => 'Varchar',
                'HTML' => 'Text'
        );

        public static $has_one = array(
                'JavascriptFile' => 'File',
                'CSSFile' => 'File',
                'SearchSection' => 'Page'
        );

        public function getCMSFields() {
                $fields = parent::getCMSFields();

                $fields->addFieldsToTab('Root.Main', array(
                        new TextField('ContentTitle', 'Content Title'),
                        TextareaField::create('Text')->setRows(1),
                        new TextField('SearchTitle', 'Search Title'),
                        ContentModuleTreeDropdownField::create('SearchSectionID', 'Search Section', 'SiteTree')->setDescription('Section of the site to limit results to'),
                        TextareaField::create('HTML', 'HTML')->setRows(5)->setDescription('HTML for Infographic'),
                        $js = ContentModuleUploadField::create('JavascriptFile', 'Javascript File')->setDescription('Javascript file for Infographic'),
                        $css = ContentModuleUploadField::create('CSSFile', 'CSS File')->setDescription('Stylesheet for Infographic'),


                ));

                $js->setFolderName('Infographic')->getValidator()->setAllowedExtensions(array('js'));
                $css->setFolderName('Infographic')->getValidator()->setAllowedExtensions(array('css'));;

                return $fields;
        }

        public function SearchPage() {
                return SearchPage::get()->first();
        }

        public function forTemplate() {
                if ($this->JavascriptFile() && $this->JavascriptFile()->exists()) {
                        Requirements::javascript($this->JavascriptFile()->getRelativePath());
                }

                if ($this->CSSFile() && $this->CSSFile()->exists()) {
                        Requirements::css($this->CSSFile()->getRelativePath());
                }

                return parent::forTemplate();
        }
}
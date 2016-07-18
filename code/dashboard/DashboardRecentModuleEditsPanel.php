<?php

if (class_exists('DashboardPanel')) {
    /**
     * Defines the "Recent Module Edits" dashboard panel type
     */
    class DashboardRecentModuleEditsPanel extends DashboardPanel
    {


        private static $db = array(
            'Count' => 'Int'
        );


        private static $defaults = array(
            'Count' => 10
        );


        private static $icon = "dashboard/images/recent-edits.png";


        private static $priority = 10;


        public function getLabel()
        {
            return _t('RecentModuleEdits.LABEL', 'Recent Module Edits');
        }


        public function getDescription()
        {
            return _t('RecentModuleEdits.DESCRIPTION', 'Shows a linked list of recently edited pages');
        }

        public function getConfiguration()
        {
            $fields = parent::getConfiguration();
            $fields->push(TextField::create("Count", _t('DashboardRecentEdits.COUNT', 'Number of Modules to display')));
            return $fields;
        }


        /**
         * Gets the recent edited pages, limited to a user provided number of records
         *
         * @return ArrayList
         */
        public function RecentEdits()
        {
            $records = ContentModule::get()->sort("LastEdited DESC")->limit($this->Count);
            $set = ArrayList::create(array());
            foreach ($records as $r) {
                $set->push(ArrayData::create(array(
                    'EditLink' => Injector::inst()->get('ContentModuleMain')->Link("edit/show/{$r->ID}"),
                    'Title' => $r->Title
                )));
            }
            return $set;
        }
    }
}
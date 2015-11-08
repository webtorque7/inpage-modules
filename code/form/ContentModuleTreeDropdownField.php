<?php
/**
 * Created by JetBrains PhpStorm.
 * User: 7
 * Date: 26/04/13
 * Time: 11:15 AM
 * To change this template use File | Settings | File Templates.
 */

class ContentModuleTreeDropdownField extends TreeDropdownField {

        private $contentModuleFieldName = '';
        private $originalFieldName = '';
        protected $_idField;

        public function setContentModuleNames($name, $contentName, $id = null) {
                $this->_idField = $id;
                $this->setName($name);
                $this->originalFieldName = $name;
                $this->contentModuleFieldName = $contentName;
        }

        public function Link($action = null) {
                $cModField = ContentModuleField::curr();
                $link = '';
                if ($cModField) {
                        $link = ContentModuleField::curr()->Link('modulefield') . '/' . $this->getName();
                        if ($action) $link .= '/' . $action;
                }
                else {
                        $link = parent::Link($action);
                }

                return $link;
        }

        public function getForm() {
                $cModField = ContentModuleField::curr();

                if ($cModField) return $cModField->getForm();

                return parent::getForm();
        }

        /**
         * @return string
         */
        public function Field($properties = array()) {
                Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang');

                Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
                Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
                Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jstree/jquery.jstree.js');
                Requirements::javascript(FRAMEWORK_DIR . '/javascript/TreeDropdownField.js');

                Requirements::css(FRAMEWORK_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery-ui.css');
                Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');

                if($this->showSearch) {
                        $emptyTitle = _t('DropdownField.CHOOSESEARCH', '(Choose or Search)', 'start value of a dropdown');
                } else {
                        $emptyTitle = _t('DropdownField.CHOOSE', '(Choose)', 'start value of a dropdown');
                }

                $record = $this->Value() ? $this->objectForKey($this->Value()) : null;
                if($record instanceof ViewableData) {
                        $title = $record->obj($this->labelField)->forTemplate();
                } elseif($record) {
                        $title = Convert::raw2xml($record->{$this->labelField});
                }
                else {
                        $title = $emptyTitle;
                }

                // TODO Implement for TreeMultiSelectField
                $metadata = array(
                    'id' => $record ? $record->ID : null,
                    'ClassName' => $record ? $record->ClassName : $this->sourceObject
                );

                $properties = array_merge(
                    $properties,
                    array(
                        'Title' => $title,
                        'EmptyTitle' => $emptyTitle,
                        'Metadata' => ($metadata) ? Convert::raw2att(Convert::raw2json($metadata)) : null
                    )
                );

                return $this->customise($properties)->renderWith('ContentModuleTreeDropdownField');
        }

        public function getModifiedName() {
                return !empty($this->originalFieldName) ? $this->originalFieldName : $this->name;
        }
}
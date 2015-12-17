<?php
/**
 * Created by JetBrains PhpStorm.
 * User: 7
 * Date: 26/04/13
 * Time: 11:15 AM
 * To change this template use File | Settings | File Templates.
 */

class ContentModuleTreeDropdownField extends TreeDropdownField
{

    private $contentModuleFieldName = '';
    private $originalFieldName = '';
    protected $_idField;

    public function setContentModuleNames($name, $contentName, $id = null)
    {
        $this->_idField = $id;
        $this->setName($name);
        $this->originalFieldName = $name;
        $this->contentModuleFieldName = $contentName;
    }

    public function Link($action = null)
    {
        $cModField = ContentModuleField::curr();
        $link = '';
        if ($cModField) {
            $link = ContentModuleField::curr()->Link('modulefield');
            $query = '';
            if (stripos($link, '?') !== false) {
                $parts = explode('?', $link);
                $link = $parts[0];
                $query = '?' . $parts[1];
            }

            $link = Controller::join_links($link, $this->getName(), $action, $query);
        } else {
            $link = parent::Link($action);
        }

        return $link;
    }

    public function getForm()
    {
        $cModField = ContentModuleField::curr();

        if ($cModField) {
            return $cModField->getForm();
        }

        return parent::getForm();
    }

    public function getModifiedName()
    {
        return !empty($this->originalFieldName) ? $this->originalFieldName : $this->name;
    }
}

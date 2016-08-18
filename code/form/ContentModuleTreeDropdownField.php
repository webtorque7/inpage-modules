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
            $name = $this->contentModuleFieldName ? $this->contentModuleFieldName : $this->getName();

            $query = '';
            if (stripos($link, '?') !== false) {
                $parts = explode('?', $link);
                $link = $parts[0];
                $query = '?' . $parts[1];
            }

            if (($pos = stripos($name, '[')) !== false) {

                $name = substr($name, 0, $pos);
                $action = substr($this->name, $pos + 1, strlen($this->name) - 1 - ($pos + 1)) . 'Tree';
            }

            $link = Controller::join_links($link, $name, $action, $query);
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

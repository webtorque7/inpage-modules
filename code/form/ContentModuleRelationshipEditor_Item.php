<?php


class ContentModuleRelationshipEditor_Item extends ViewableData
{

    private $parent, $item, $_canEdit;

    public function __construct($parent, $item)
    {
        $this->parent = $parent;
        $this->item = $item;
    }

    public function Fields($xmlSafe = true)
    {
        $list = $this->parent->FieldList();
        foreach ($list as $fieldName => $fieldTitle) {
            $value = "";

            // This supports simple FieldName syntax
            if (strpos($fieldName, '.') === false) {
                $value = ($this->item->XML_val($fieldName) && $xmlSafe)
                    ? $this->item->XML_val($fieldName)
                    : $this->item->RAW_val($fieldName);
                // This support the syntax fieldName = Relation.RelatedField
            } else {
                $fieldNameParts = explode('.', $fieldName);
                $tmpItem = $this->item;
                for ($j = 0; $j < sizeof($fieldNameParts); $j++) {
                    $relationMethod = $fieldNameParts[$j];
                    $idField = $relationMethod . 'ID';
                    if ($j == sizeof($fieldNameParts) - 1) {
                        if ($tmpItem) {
                            $value = $tmpItem->$relationMethod;
                        }
                    } else {
                        if ($tmpItem->hasMethod($relationMethod)) {
                            if ($tmpItem) {
                                $tmpItem = $tmpItem->$relationMethod();
                            }
                        }
                    }
                }
            }


            //escape
            if ($escape = $this->parent->fieldEscape) {
                foreach ($escape as $search => $replace) {
                    $value = str_replace($search, $replace, $value);
                }
            }

            $fields[] = new ArrayData(
                array(
                    "Name" => $fieldName,
                    "Title" => $fieldTitle,
                    "Value" => $value,
                )
            );
        }
        return new ArrayList($fields);
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setCanEdit($bool)
    {
        $this->_canEdit = $bool;
        return $this;
    }

    public function getCanEdit()
    {
        return $this->_canEdit;
    }
}

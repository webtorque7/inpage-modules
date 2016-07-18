<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 6/07/2016
 * Time: 9:14 AM
 */
class VisualEditorSilverStripeNavigator extends SilverStripeNavigator
{

    public function getItems()
    {
        $items = ArrayList::create();

        $items->push(ArrayData::create(array(
            'Title' => _t('ContentController.DRAFT', 'Draft', 'Used for the Switch between draft and published view mode. Needs to be a short label'),
            'Name' => 'StageLink',
            'Link' => $this->record->PreviewLink() . '?stage=Stage&page-editor=true',
            'isActive' => true
        )));

        $items->push(ArrayData::create(array(
            'Title' => _t('ContentController.PUBLISHED', 'Published', 'Used for the Switch between draft and published view mode. Needs to be a short label'),
            'Name' => 'LiveLink',
            'Link' => $this->record->PreviewLink() . '?stage=Live&page-editor=true',
            'isActive' => false
        )));

        return $items;
    }
}
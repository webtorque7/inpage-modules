<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 9/11/2015
 * Time: 5:28 PM
 */
class ContentModuleSiteTreeTranslatableExtension extends DataExtension
{
    public function onTranslatableCreate($saveTranslation)
    {
        if ($saveTranslation) {
            //create new modules
            $manyManys = array_reverse($page->manyMany());

            if (!empty($manyManys)) {
                foreach ($manyManys as $relationship => $class) {
                    if ($class === 'ContentModule' || ($class instanceof ContentModule)) {
                        if ($original = $this->owner->getTranslation(Translatable::default_locale())) {
                            foreach ($original->$relationship() as $module) {
                                //create new module
                                $newModule = Object::create(get_class($module));
                                $newModule->Title = $module->Title . ' - ' . Translatable::get_current_locale();
                                $newModule->Locale = Translatable::get_current_locale();
                                $newModule->OriginalID = $original->ID;
                                $newModule->write();
                                $this->owner->$relationship()->add($newModule);
                            }
                        }
                    }
                }
        }
    }
}

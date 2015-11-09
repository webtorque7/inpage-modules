<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 9/11/2015
 * Time: 5:28 PM
 */
class ContentModuleTranslatableExtension extends DataExtension
{
	public function onTranslatableCreate($saveTranslation) {
		if ($saveTranslation) {
			//create new modules
			if ($original = $this->owner->getTranslation(Translatable::default_locale())) {
				foreach ($original->ContentModules() as $module) {
					//create new module
					$newModule = Object::create(get_class($module));
					$newModule->Title = $module->Title . ' - ' . Translatable::get_current_locale();
					$newModule->write();
					$this->owner->ContentModules()->add($newModule);
				}
			}
		}
	}

}
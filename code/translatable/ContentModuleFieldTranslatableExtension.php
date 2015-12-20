<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 10/11/2015
 * Time: 1:22 PM
 */
class ContentModuleFieldTranslatableExtension extends Extension
{
    public function onBeforeHandleAction(SS_HTTPRequest $request, $action)
    {
        if ($locale = $request->getVar('Locale')) {
            Translatable::set_current_locale($locale);
        }
    }
}

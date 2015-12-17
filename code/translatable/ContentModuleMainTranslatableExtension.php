<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 9/11/2015
 * Time: 2:51 PM
 */
class ContentModuleMainTranslatableExtension extends Extension
{
    /**
     * Returns a form with all languages with languages already used appearing first.
     *
     * @return Form
     */
    public function updateSearchForm(Form $form)
    {
        $member = Member::currentUser(); //check to see if the current user can switch langs or not
        if (Permission::checkMember($member, 'VIEW_LANGS')) {
            $field = new LanguageDropdownField(
                'Locale',
                _t('CMSMain.LANGUAGEDROPDOWNLABEL', 'Language'),
                array(),
                'SiteTree',
                'Locale-English',
                singleton('SiteTree')
            );

            $field
                ->setValue(Translatable::get_current_locale())
                ->setForm($form);
        } else {
            // user doesn't have permission to switch langs
            // so just show a string displaying current language
            $field = new LiteralField(
                'Locale',
                i18n::get_locale_name(Translatable::get_current_locale())
            );
        }

        $form->Fields()->unshift($field);
    }
}

<?php

/**
 * Base module for implementing a Header. Inherit this module to create a header, default header module can
 * be selecting in SiteConfig. Pages can overwrite the default header
 */
class HeaderBaseModule extends ContentModule
{

}

class HeaderBaseModule_Controller extends ModuleController
{
    public function getMenu($depth = 1)
    {
        return $this->currController()->getMenu($depth);
    }
}
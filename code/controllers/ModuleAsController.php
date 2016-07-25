<?php

class ModuleAsController extends Controller
{
    /**
     * @param ContentModule $module
     * @param ContentController|null $contentController
     * @param null $action
     * @return ModuleController
     */
    public static function controller_for(ContentModule $module, ContentController $contentController = null, $action = null)
    {
        if (empty($contentController)) $contentController = Controller::curr();

        if ($module->class == 'ContentModule') {
            $controller = "ModuleController";
        } else {
            $ancestry = ClassInfo::ancestry($module->class);
            while ($class = array_pop($ancestry)) {
                if (class_exists($class . "_Controller")) {
                    break;
                }
            }
            $controller = ($class !== null) ? "{$class}_Controller" : 'ModuleController';
        }

        if ($action && class_exists($controller . '_' . ucfirst($action))) {
            $controller = $controller . '_' . ucfirst($action);
        }

        return !empty($controller) && class_exists($controller) ? Injector::inst()->create($controller, $module, $contentController) : $module;
    }

    public static function module_controller_for_request(ContentController $contentController, SS_HTTPRequest $request, $relationship = 'ContentModules')
    {
        $moduleURLSegment = $request->shift();

        if ($module = $contentController->data()->$relationship()->filter('URLSegment', $moduleURLSegment)->first()) {
            $controller = self::controller_for($module, $contentController);

            //backwards compatibility support for modules handling actions directly
            //should move to using controllers to handle actions
            if ($controller instanceof ModuleController) {
                return $controller->handleRequest($request, new DataModel());
            } else {
                $action = $request->shift();
                if ($controller->hasMethod($action)) {
                    return $controller->$action($request);
                }
            }
        }
    }
}
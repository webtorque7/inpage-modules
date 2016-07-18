<?php

/**
 * Created by PhpStorm.
 * User: Conrad
 * Date: 24/06/2016
 * Time: 9:06 AM
 */
class ModuleController extends Controller
{
    /**
     * @var ContentModule
     */
    protected $dataRecord;

    protected $currentController;

    public function __construct(ContentModule $dataRecord, ContentController $curr = null)
    {
        $this->dataRecord = $dataRecord;
        $this->currentController = $curr;

        $this->setFailover($this->dataRecord);

        parent::__construct();
    }

    public function init()
    {
        parent::init();
    }

    public function handleRequest(SS_HTTPRequest $request, DataModel $model)
    {
        return parent::handleRequest($request, $model);
    }

    public function data()
    {
        return $this->dataRecord;
    }

    public function currController()
    {
        return $this->currentController;
    }

    /**
     * Overwrite the link function from the Controller
     *
     * @param null $action
     * @return mixed
     */
    public function Link($action = null)
    {
        return $this->data()->Link($action);
    }

    /**
     * Determines whether ajax or not and responds accordingly
     *
     * @param string $action
     * @param null $customise
     * @return array|SS_HTTPResponse|ViewableData_Customised
     */
    public function actionResponse($action, $customise = null)
    {
        if ($this->request->isAjax()) {
            return $this->ajaxResponse($action, $customise);
        }

        $this->response->addHeader('Vary', 'Accept');
        $viewer = $this->getViewer($action);
        $templates = $viewer->templates();
        $mainFile = basename($templates['main']);
        $main = substr($mainFile, 0, strpos($mainFile, '.'));
        $layoutFile = basename($templates['Layout']);
        $layout = substr($layoutFile, 0, strpos($layoutFile, '.'));
        $templates = array($layout, $main);

        return $customise ? $this->customise($customise)->renderWith($templates) : $this->renderWith($templates);
    }

    /**
     * Handles returning a JSON response, makes sure Content-Type header is set
     *
     * @param array $array
     * @param bool $isJson Is the passed string already a json string
     * @return SS_HTTPResponse
     */
    public function jsonResponse($array, $isJson = false)
    {
        $json = $array;
        if (!$isJson) {
            $json = Convert::raw2json($array);
        }

        $response = new SS_HTTPResponse($json);
        $response->addHeader('Content-Type', 'application/json');
        $response->addHeader('Vary', 'Accept');

        return $response;
    }

    /**
     * Handles returning the template for an ajax response
     *
     * @param string $action The action to respond to
     * @param null $customise
     * @return SS_HTTPResponse
     */
    public function ajaxResponse($action, $customise = null)
    {
        $viewer = $this->getViewer($action);
        $templates = $viewer->templates();
        $viewer->setTemplateFile('main', $templates['Layout']);
        $viewer->setTemplateFile('Layout', null);

        return $this->jsonResponse(
            array(
                'Title' => ($this->MetaTitle ? $this->MetaTitle : $this->Title) . ' &raquo; ' . SiteConfig::current_site_config()->Title,
                'Content' => $viewer->process($customise ? $this->customise($customise) : $this),
                'PageID' => $this->ID,
                'Segment' => $this->URLSegment,
                'Level' => $this->CurrentLevel(),
                'ExtraBackgroundClass' => $this->ExtraBackgroundClass()
            )
        );
    }

    public function setFailover($failover)
    {
        $this->failover = $failover;
    }
}
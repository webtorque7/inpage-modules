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
}
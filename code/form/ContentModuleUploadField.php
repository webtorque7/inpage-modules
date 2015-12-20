<?php
/**
 * Created by JetBrains PhpStorm.
 * User: 7
 * Date: 26/04/13
 * Time: 11:15 AM
 * To change this template use File | Settings | File Templates.
 */

class ContentModuleUploadField extends UploadField
{

    private $contentModuleFieldName = '';
    private $originalFieldName = '';
    protected $_idField;

    public function setContentModuleNames($name, $contentName, $id = null)
    {
        $this->_idField = $id;
        $this->setName($contentName);
        $this->originalFieldName = $name;
        $this->contentModuleFieldName = $contentName;
    }

    public function Link($action = null)
    {
        $cModField = ContentModuleField::curr();
        $link = '';
                //pass the id so $OtherID is always parsed
                if ($cModField) {
                    $name = $this->contentModuleFieldName ? $this->contentModuleFieldName : $this->getName();

                    $link = $cModField->Link('modulefield');

                    $query = '';
                    if (stripos($link, '?') !== false) {
                        $parts = explode('?', $link);
                        $link = $parts[0];
                        $query = '?' . $parts[1];
                    }

                    $link = Controller::join_links($link, $name, $action, $query);
                } else {
                    $link = parent::Link($action);
                }

        return $link;
    }

    public function getForm()
    {
        $cModField = ContentModuleField::curr();

        if ($cModField) {
            return $cModField->getForm();
        }

        return parent::getForm();
    }

        /**
         * Action to handle upload of a single file
         *
         * @param SS_HTTPRequest $request
         * @return string json
         */
    private static $allowed_actions = array(
        'upload'
    );


    public function modifiedUpload(SS_HTTPRequest $request)
    {
        if ($this->isDisabled() || $this->isReadonly() || !$this->canUpload()) {
            return $this->httpError(403);
        }

                // Protect against CSRF on destructive action
                $token = $this->getForm()->getSecurityToken();
                //if(!$token->checkRequest($request)) return $this->httpError(400);

                $name = $this->getName();
        $contentFieldName = $this->contentModuleFieldName;

        $postVars = $request->postVars();
        $tmpfile = $request->postVar('ContentModule');
        $record = $this->getRecord();

                // Check if the file has been uploaded into the temporary storage.
                if (!$tmpfile) {
                    $return = array('error' => _t('UploadField.FIELDNOTSET', 'File information not found'));
                } else {
                    $return = array(
                                'name' => $tmpfile['name'][$this->getRecord()->ID][$name],
                                'size' => $tmpfile['size'][$this->getRecord()->ID][$name],
                                'type' => $tmpfile['type'][$this->getRecord()->ID][$name],
                                'error' => $tmpfile['error'][$this->getRecord()->ID][$name],
                                'tmp_name' => $tmpfile['tmp_name'][$this->getRecord()->ID][$name]
                        );
                }

                // Check for constraints on the record to which the file will be attached.
                if (!$return['error'] && $this->relationAutoSetting && $record && $record->exists()) {
                    $tooManyFiles = false;
                        // Some relationships allow many files to be attached.
                        if ($this->getConfig('allowedMaxFileNumber') && ($record->has_many($name) || $record->many_many($name))) {
                            if (!$record->isInDB()) {
                                $record->write();
                            }
                            $tooManyFiles = $record->{$name}()->count() >= $this->getConfig('allowedMaxFileNumber');
                                // has_one only allows one file at any given time.
                        } elseif ($record->has_one($name)) {
                            // If we're allowed to replace an existing file, clear out the old one
                                if ($record->$name && $this->getConfig('replaceExistingFile')) {
                                    $record->$name = null;
                                }
                            $tooManyFiles = $record->{$name}() && $record->{$name}()->exists();
                        }

                        // Report the constraint violation.
                        if ($tooManyFiles) {
                            if (!$this->getConfig('allowedMaxFileNumber')) {
                                $this->setConfig('allowedMaxFileNumber', 1);
                            }
                            $return['error'] = _t(
                                        'UploadField.MAXNUMBEROFFILES',
                                        'Max number of {count} file(s) exceeded.',
                                        array('count' => $this->getConfig('allowedMaxFileNumber'))
                                );
                        }
                }

                // Process the uploaded file
                if (!$return['error']) {
                    $fileObject = null;

                    if ($this->relationAutoSetting) {
                        // Search for relations that can hold the uploaded files.
                                if ($relationClass = $this->getRelationAutosetClass()) {
                                    // Create new object explicitly. Otherwise rely on Upload::load to choose the class.
                                        $fileObject = Object::create($relationClass);
                                }
                    }

                        // Get the uploaded file into a new file object.
                        try {
                            $this->upload->loadIntoFile($return, $fileObject, $this->folderName);
                        } catch (Exception $e) {
                            // we shouldn't get an error here, but just in case
                                $return['error'] = $e->getMessage();
                        }

                    if (!$return['error']) {
                        if ($this->upload->isError()) {
                            $return['error'] = implode(' '.PHP_EOL, $this->upload->getErrors());
                        } else {
                            $file = $this->upload->getFile();

                                        // Attach the file to the related record.
                                        if ($this->relationAutoSetting) {
                                            $this->attachFile($file);
                                        }

                                        // Collect all output data.
                                        $file =  $this->customiseFile($file);
                            $return = array_merge($return, array(
                                                'id' => $file->ID,
                                                'name' => $file->getTitle() . '.' . $file->getExtension(),
                                                'url' => $file->getURL(),
                                                'thumbnail_url' => $file->UploadFieldThumbnailURL,
                                                'edit_url' => $file->UploadFieldEditLink,
                                                'size' => $file->getAbsoluteSize(),
                                                'buttons' => $file->UploadFieldFileButtons
                                        ));
                        }
                    }
                }
        $response = new SS_HTTPResponse(Convert::raw2json(array($return)));
        $response->addHeader('Content-Type', 'text/plain');
        return $response;
    }

    public function getModifiedName()
    {
        return !empty($this->originalFieldName) ? $this->originalFieldName : $this->name;
    }

        /**
         * @return SS_List
         */
        public function getItems()
        {
            $name = $this->getModifiedName();
            if (!$this->items || !$this->items->exists()) {
                $record = $this->getRecord();
                $this->items = array();
                        // Try to auto-detect relationship
                        if ($record && $record->exists()) {
                            if ($record->has_many($name) || $record->many_many($name)) {
                                // Ensure relationship is cast to an array, as we can't alter the items of a DataList/RelationList
                                        // (see below)
                                        $this->items = $record->{$name}()->toArray();
                            } elseif ($record->has_one($name)) {
                                $item = $record->{$name}();
                                if ($item && $item->exists()) {
                                    $this->items = array($record->{$name}());
                                }
                            }
                        }
                $this->items = new ArrayList($this->items);
                        // hack to provide $UploadFieldThumbnailURL, $hasRelation and $UploadFieldEditLink in template for each
                        // file
                        if ($this->items->exists()) {
                            foreach ($this->items as $i=>$file) {
                                $this->items[$i] = $this->customiseFile($file);
                                if (!$file->canView()) {
                                    unset($this->items[$i]);
                                } // Respect model permissions
                            }
                        }
            }
            return $this->items;
        }

    public function setValue($value, $record = null)
    {
        $items = new ArrayList();

        // Determine format of presented data
        if (empty($value) && $record) {
            // If a record is given as a second parameter, but no submitted values,
            // then we should inspect this instead for the form values

            if (($record instanceof DataObject) && $record->hasMethod($this->getFieldName())) {
                // If given a dataobject use reflection to extract details

                $data = $record->{$this->getFieldName()}();

                if ($data instanceof DataObject) {
                    // If has_one, add sole item to default list
                    $items->push($data);
                } elseif ($data instanceof SS_List) {
                    // For many_many and has_many relations we can use the relation list directly
                    $items = $data;
                }
            } elseif ($record instanceof SS_List) {
                // If directly passing a list then save the items directly
                $items = $record;
            }
        } elseif (!empty($value['Files'])) {
            // If value is given as an array (such as a posted form), extract File IDs from this
            $class = $this->getRelationAutosetClass();
            $items = DataObject::get($class)->byIDs($value['Files']);
        }

        // If javascript is disabled, direct file upload (non-html5 style) can
        // trigger a single or multiple file submission. Note that this may be
        // included in addition to re-submitted File IDs as above, so these
        // should be added to the list instead of operated on independently.
        if ($uploadedFiles = $this->extractUploadedFileData($value)) {
            foreach ($uploadedFiles as $tempFile) {
                $file = $this->saveTemporaryFile($tempFile, $error);
                if ($file) {
                    $items->add($file);
                } else {
                    throw new ValidationException($error);
                }
            }
        }

        // Filter items by what's allowed to be viewed
        $filteredItems = new ArrayList();
        $fileIDs = array();
        foreach ($items as $file) {
            if ($file->exists() && $file->canView()) {
                $filteredItems->push($file);
                $fileIDs[] = $file->ID;
            }
        }

        // Filter and cache updated item list
        $this->items = $filteredItems;
        // Same format as posted form values for this field. Also ensures that
        // $this->setValue($this->getValue()); is non-destructive
        $value = $fileIDs ? array('Files' => $fileIDs) : null;

        // Set value using parent
        return parent::setValue($value, $record);
    }

    public function getFieldName()
    {
        return !empty($this->contentModuleFieldName) ? $this->originalFieldName : $this->getName();
    }

    public function saveInto(DataObjectInterface $record)
    {
        // Check required relation details are available
        $fieldname = $this->getFieldName();

        if (!$fieldname) {
            return $this;
        }

        //echo $this->Value();exit;
        // Get details to save
        $idList = $this->getItemIDs();

        // Check type of relation
        $relation = $record->hasMethod($fieldname) ? $record->$fieldname() : null;
        if ($relation && ($relation instanceof RelationList || $relation instanceof UnsavedRelationList)) {
            // has_many or many_many
            $relation->setByIDList($idList);
        } elseif ($record->has_one($fieldname)) {
            // has_one
            $record->{"{$fieldname}ID"} = $idList ? reset($idList) : 0;
        }
        return $this;
    }

    /**
     * Given an array of post variables, extract all temporary file data into an array
     *
     * @param array $postVars Array of posted form data
     * @return array List of temporary file data
     */
    protected function extractUploadedFileData($postVars)
    {
        $tmpFiles = array();

        if (!empty($this->contentModuleFieldName)) {
            $postVars = $this->request->postVar('ContentModule');
            //bit of a hack, but it stopped working for some reason
            if (empty($postVars) && !empty($_FILES['ContentModule'])) {
                $postVars = $_FILES['ContentModule'];
            }
            if (!empty($postVars['tmp_name'])
                && is_array($postVars['tmp_name'])
            ) {
                foreach ($postVars['tmp_name'] as $index => $tmp) {
                    if (isset($postVars['tmp_name'][$index][$this->originalFieldName])) {
                        for ($i = 0; $i < count($postVars['tmp_name'][$index][$this->originalFieldName]['Uploads']); $i++) {
                            // Skip if "empty" file
                        if (empty($postVars['tmp_name'][$index][$this->originalFieldName]['Uploads'][$i])) {
                            continue;
                        }
                            $tmpFile = array();
                            foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
                                $tmpFile[$field] = $postVars[$field][$index][$this->originalFieldName]['Uploads'][$i];
                            }
                            $tmpFiles[] = $tmpFile;
                        }
                    }
                }
            }
            return $tmpFiles;
        }

        // Note: Format of posted file parameters in php is a feature of using
        // <input name='{$Name}[Uploads][]' /> for multiple file uploads
        if (!empty($postVars['tmp_name'])
            && is_array($postVars['tmp_name'])
            && !empty($postVars['tmp_name']['Uploads'])
        ) {
            for ($i = 0; $i < count($postVars['tmp_name']['Uploads']); $i++) {
                // Skip if "empty" file
                if (empty($postVars['tmp_name']['Uploads'][$i])) {
                    continue;
                }
                $tmpFile = array();
                foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
                    $tmpFile[$field] = $postVars[$field]['Uploads'][$i];
                }
                $tmpFiles[] = $tmpFile;
            }
        } elseif (!empty($postVars['tmp_name'])) {
            // Fallback to allow single file uploads (method used by AssetUploadField)
            $tmpFiles[] = $postVars;
        }

        return $tmpFiles;
    }
}

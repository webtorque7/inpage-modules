<?php
/**
 * Created by JetBrains PhpStorm.
 * User: 7
 * Date: 26/04/13
 * Time: 11:15 AM
 * To change this template use File | Settings | File Templates.
 */

class ContentModuleUploadField extends UploadField {

        private $contentModuleFieldName = '';
        private $originalFieldName = '';
        protected $_idField;

        public function setContentModuleNames($name, $contentName, $id = null) {
                $this->_idField = $id;
                $this->setName($name);
                $this->originalFieldName = $name;
                $this->contentModuleFieldName = $contentName;
        }

        public function Link($action = null) {
                $cModField = ContentModuleField::curr();
                $link = '';
                //pass the id so $OtherID is always parsed
                if ($cModField) {
                        $link = $cModField->Link('modulefield') . '/' . $this->getName();
                        if ($action) $link .= '/' . $action;
                }
                else {
                        $link = parent::Link($action);
                }

                return $link;
        }

        public function getForm() {
                $cModField = ContentModuleField::curr();

                if ($cModField) return $cModField->getForm();

                return parent::getForm();
        }

        /**
         * Action to handle upload of a single file
         *
         * @param SS_HTTPRequest $request
         * @return string json
         */
        public function upload(SS_HTTPRequest $request) {

                if ($this->contentModuleFieldName) {
                        return $this->modifiedUpload($request);
                }

                return parent::upload($request);
        }

        public function modifiedUpload(SS_HTTPRequest $request) {
                if($this->isDisabled() || $this->isReadonly() || !$this->canUpload()) {
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
                                if(!$record->isInDB()) $record->write();
                                $tooManyFiles = $record->{$name}()->count() >= $this->getConfig('allowedMaxFileNumber');
                                // has_one only allows one file at any given time.
                        } elseif($record->has_one($name)) {
                                // If we're allowed to replace an existing file, clear out the old one
                                if($record->$name && $this->getConfig('replaceExistingFile')) {
                                        $record->$name = null;
                                }
                                $tooManyFiles = $record->{$name}() && $record->{$name}()->exists();
                        }

                        // Report the constraint violation.
                        if ($tooManyFiles) {
                                if(!$this->getConfig('allowedMaxFileNumber')) $this->setConfig('allowedMaxFileNumber', 1);
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

        public function getModifiedName() {
                return !empty($this->originalFieldName) ? $this->originalFieldName : $this->name;
        }

        /**
         * @return SS_List
         */
        public function getItems() {
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
                                } elseif($record->has_one($name)) {
                                        $item = $record->{$name}();
                                        if ($item && $item->exists())
                                                $this->items = array($record->{$name}());
                                }
                        }
                        $this->items = new ArrayList($this->items);
                        // hack to provide $UploadFieldThumbnailURL, $hasRelation and $UploadFieldEditLink in template for each
                        // file
                        if ($this->items->exists()) {
                                foreach ($this->items as $i=>$file) {
                                        $this->items[$i] = $this->customiseFile($file);
                                        if(!$file->canView()) unset($this->items[$i]); // Respect model permissions
                                }
                        }
                }
                return $this->items;
        }
}
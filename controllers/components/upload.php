<?php

/**
 * Description of upload
 * @author Kevin Andrews <kevin.andrews@atomjuice.com>
 */
class UploadComponent extends Component {
    
    const MAX_SIZE = 1024000;
    
    private $mimeList = array(
        'csv' => 'text/csv',
        'csv' => 'text/plain',
    );
    
    public $components = array('Ext.Request');
    
    /**
     * @var array
     */
    private $files = array();
    
    /**
     * @var array 
     */
    public $errors = array();
    
    /**
     *
     * @var RequestComponent
     */
    public $Request;
    
    /**
     * {@inheritdoc}
     */
    public function init(&$controller) {
        parent::init($controller);
    }
    
    /**
     * {@inheritdoc}
     */
    public function initialize(&$controller) {
        parent::initialize($controller);
    }
    
    /**
     * {@inheritdoc}
     */
    public function startup(&$controller) {
        $this->files = $this->Request->getFiles();
        $this->process($this->files);
        parent::startup($controller);
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeRedirect(&$controller, $url, $status = null, $exit = true) {
        parent::beforeRedirect($controller, $url, $status, $exit);
    }
    
    /**
     * 
     * @param array $files
     */
    private function process(array $files) {
        foreach ($files as $name => $details) {
            try {
                $this->validateForm($details);
                $this->validateSize($details);
                $this->validateMime($name, $details);
                
            } catch (Exception $exc) {
                $this->errors[$name][] = $exc->getMessage();
            }
        }
    }

    /**
     * 
     * @param array $details
     * @throws RuntimeException
     */
    private function validateForm($details)
    {
        switch ($details['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit.');
            default:
                throw new RuntimeException('Unknown error.');
        }
    }
    
    /**
     * 
     * @param string $name
     * @return boolean
     */
    public function validate($name)
    {
        if(!$this->hasErrors($name)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * @param array $details
     * @throws RuntimeException
     */
    private function validateMime($name, $details)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileExt = $finfo->file($details['tmp_name']);
        $ext = array_search($fileExt, $this->mimeList, true);
        
        if (false === $ext) {
            throw new RuntimeException('Invalid file format.');
        } else {
            $this->files[$name]['ext'] = $ext;
        }
        
        return true;
    }

    /**
     * 
     * @param array $details
     * @return boolean
     * @throws RuntimeException
     */
    private function validateSize($details)
    {
        if ($details['size'] > self::MAX_SIZE) {
            throw new RuntimeException('Exceeded filesize limit.');
        }
        
        return true;
    }
    
    /**
     * Used my $this->get() to validate the file name still resides inside upload path
     * @param string $path
     * @param string $name
     */
    private function validateMovePath($formName, $path, $fileName)
    {
        $fullPath = $path . $fileName;
        $resultingPath = realpath(dirname($fullPath));
        
        /** check upload directory exists and squish down symlinks and ../ .. . references */
        if(!$resultingPath) {
            $this->errors[$formName][] = "Upload directory including directories attached to the filename doesn't appear to exist.";
        }
        
        /** check the resulting realpath contains the hard defined upload path from the start */
        if(!strpos($resultingPath, $path) === 0) {
            $this->errors[$formName][] = "File attempting to upload outside of defined upload directory, check resulting upload name.";
        }
        
        return $fullPath;
    }
    
    /**
     * 
     * @param string $name
     * @return boolean
     */
    private function hasErrors($name=null)
    {
        if($name === null) {
            return (!empty($this->errors)) ? true : false;
        } else {
            return (array_key_exists($name, $this->errors) && !empty($this->errors[$name])) ? true : false;
        }
    }
    
    /**
     * 
     * @param string $name
     * @return array
     */
    public function getErrors($name=null)
    {
        if($name === null) {
            return (!empty($this->errors) ? $this->errors : array());
        } else {
            return (array_key_exists($name, $this->errors) && !empty($this->errors[$name])) ? $this->errors[$name] : array();
        }
    }
    
    /**
     * Validates, Uploads, Moves and Returns the referenced file
     * @param string $formElementName form name
     * @param string $newFileName
     * @return File | boolean 
     */
    public function upload($formElementName, $newFileName) 
    {
        $fileName = $newFileName . ((isset($this->files[$formElementName]['ext'])) ? '.' . $this->files[$formElementName]['ext'] : '');
        $fullPath = $this->validateMovePath($formElementName, UPLOAD_DIR, $fileName);
        
        if($this->validate($formElementName)) {
            $oldName = $this->files[$formElementName]['tmp_name'];
            $moved = move_uploaded_file($oldName, $fullPath);
            return ($moved) ? new File( $fileName, false, 0755) : false;
        }
        
        return false;
    }
}

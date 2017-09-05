<?php

use App\Custom\Exception\FileException;

/**
 * Description of upload
 * @author Kevin Andrews <kevin.andrews@atomjuice.com>
 */
class UploadComponent extends Component
{
    /** @todo move to app config? */
    const MAX_SIZE = 1024000;

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
    public function init(&$controller)
    {
        parent::init($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(&$controller)
    {
        parent::initialize($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function startup(&$controller)
    {
        $this->files = $this->Request->getFiles();
        $this->process($this->files);
        parent::startup($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRedirect(&$controller, $url, $status = null, $exit = true)
    {
        parent::beforeRedirect($controller, $url, $status, $exit);
    }

    /**
     *
     * @param array $files
     */
    private function process(array $files)
    {
        foreach ($files as $name => $details) {
            try {
                $this->validateForm($details);
                $this->validateSize($details);
                $this->validateMime($name, $details['tmp_name']);

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
     * @param string $name
     * @return boolean
     */
    public function validate($name)
    {
        if (!$this->hasErrors($name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $fileLocation
     * @return string
     */
    public function getFileExtension($fileLocation)
    {
        $mimes = new \Mimey\MimeTypes;
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $mimes->getExtension($finfo->file($fileLocation));
    }

    /**
     *
     * @param array $tmpName
     * @throws RuntimeException
     */
    private function validateMime($fileName, $tmpName)
    {
        $extension = $this->getFileExtension($tmpName);

        if (isset($extension)) {
            $this->files[$fileName]['extension'] = $extension;
            return $extension;
        }
        throw new RuntimeException('Unknown file format.');
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
     *
     * @param string $name
     * @return boolean
     */
    public function hasErrors($name = null)
    {
        if ($name === null) {
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
    public function getErrors($name = null)
    {
        if ($name === null) {
            return (!empty($this->errors) ? $this->errors : array());
        } else {
            return (array_key_exists($name, $this->errors) && !empty($this->errors[$name])) ? $this->errors[$name] : array();
        }
    }

    /**
     * Validates, Uploads, Moves and Returns the referenced file
     * @param string $formElementName form name
     * @param string $fileName
     * @param array $allowedFileTypes
     * @param string $directoryPrefix
     * @return bool|File
     * @throws FileException
     */
    public function upload($formElementName, $fileName, $allowedFileTypes = [], $directoryPrefix = '.')
    {
        $file = $this->files[$formElementName];
        $extension = (isset($file['extension']) ? $file['extension'] : $this->getFileExtension($file['tmp_name']));

        if (!in_array($extension, $allowedFileTypes)) {
            throw new FileException("File type $extension not allowed");
        }

        if (strpos($fileName, $extension) === false) {
            $fileName = $fileName . '.' . $extension;
        }

        $folder = realpath(UPLOAD_DIR . DS . basename($directoryPrefix));
        if ($folder === false) {
            throw new FileException("Upload directory $directoryPrefix does not exist");
        }
        $filePath = $folder . DS . basename($fileName);

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return new File($filePath, false, 0655);
        }
        throw new FileException('Unable to move uploaded file');
    }
}

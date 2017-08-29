<?php

App::import('Core', 'RequestHandler');

/**
 * Extended request handling component for cakephp controllers
 * @author kandrews
 */
class RequestComponent extends RequestHandlerComponent
{

    private $getData = array();
    private $postData = array();
    private $files = array();
    private $content = '';
    private $contentTruncated = false;
    private $headers = array();

    public function getParams()
    {
        return $this->getData;
    }

    public function getPostData()
    {
        return $this->postData;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param array|string $values
     * @return bool
     */
    public function hasPostData($values)
    {
        if (!is_array($values)) {
            return !empty($this->postData[$values]);
        }
        foreach ($values as $value) {
            if (empty($this->postData[$value])) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array|string $files
     * @return bool
     */
    public function hasFiles($files)
    {
        foreach ($files as $file) {
            if (empty($this->files[$file])) {
                return false;
            }
            if ($this->files[$file]['error'] === UPLOAD_ERR_NO_FILE) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns a url GET parameter by name
     * @param type $param
     * @return value of parameter or false
     */
    public function getParam($param)
    {
        return $this->get('getData', $param);
    }

    /**
     * Returns URL parameter split into key=>value pairs but two delimiters.
     * @param string $param
     * @param string $delimiter '~'
     * @param string $subDelimiter ':'
     * @return array
     */
    public function getParamSplitBy($param, $delimiter='~', $subDelimiter=':')
    {
        $paramChunks = array_chunk(preg_split('/('.$delimiter.'|'.$subDelimiter.')/', urldecode($this->getParam($param))), 2);

        $keys = array_column($paramChunks, 0);
        $unpaddedValues = array_column($paramChunks, 1);

        $keyCount = count($keys);
        $unpaddedCount = count($unpaddedValues);

        $values = ($keyCount > count($unpaddedCount)) ?
            array_pad($unpaddedValues, $keyCount, '') :
            $unpaddedValues;

        $paramPairs = array_combine($keys, $values);

        return $paramPairs;
    }

    /**
     * Returns a POST form parameter by name
     * @param type $param
     */
    public function getPost($param)
    {
        return $this->get('postData', $param);
    }

    /**
     * Returns a request header by name
     * @param string $param
     */
    public function getHeader($param)
    {
        return $this->get('headers', $param);
    }

    /**
     *
     * @param type $param
     * @return array
     */
    public function getFile($param)
    {
        return $this->get('files', $param);
    }

    /**
     * Returns the raw HTTP content for JSON/XML requests
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Was raw post data truncated to avoid PHP memory overflow
     * @return boolean
     */
    public function isContentTruncated()
    {
        return $this->contentTruncated;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function startup(&$controller)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(&$controller, $settings = array())
    {
        $this->getData = array_map(
            function($value) {
                return (is_string($value)) ? urldecode($value) : $value;
            },
            array_merge($_GET, $controller->params)
        );

        $this->postData = $_POST;
        $this->headers = getallheaders();
        $this->processPostContent(fopen('php://input', 'r'));
        $this->files = $_FILES;
        parent::initialize($controller, $settings);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRedirect(&$controller, $url, $status = null)
    {
        parent::beforeRedirect($controller, $url, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function requestedWith($type = null)
    {
        parent::requestedWith($type);
    }

    /**
     *
     * @param string $type
     * @param string $param
     * @return type
     */
    private function get($type, $param)
    {
        $value = false;

        if(property_exists($this, $type) && isset($this->{$type}[$param])) {
            $value = $this->{$type}[$param];
        }

        return $value;
    }

    /**
     *
     * @param handle $stream
     */
    private function processPostContent($stream)
    {
        $postSize = $this->getPostMaxSize();
        rewind($stream);
        $this->content = stream_get_contents($stream, $postSize);
        $endOfFile = stream_get_meta_data($stream)['eof'];
        $this->contentTruncated = ($endOfFile) ? false : true;
    }


    /**
     * Retrieve post_max_size in bytes
     * @return integer
     */
    public function getPostMaxSize()
    {
        return $this->parseSize(ini_get('post_max_size'));
    }

    /**
     * Parse friendly size to integer in bytes
     * @param string $size 8M
     * @return integer
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return $bytesSize = $size * pow(1024, stripos('bkmgtpezy', $unit[0]));
        }

        return round($size);
    }
}
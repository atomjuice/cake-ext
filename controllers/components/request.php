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
        $paramChunks = array_chunk(preg_split('/('.$delimiter.'|'.$subDelimiter.')/', $this->getParam($param)), 2);
        
        $keys = array_column($paramChunks, 0);
        $unpaddedValues = array_column($paramChunks, 1);
        
        $values = (count($keys) > count($unpaddedValues)) ? 
            array_pad($unpaddedValues, count($keys), '') : 
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
         /** @todo remove url from get string and split into pairs */
        $this->getData = $_GET;
        $this->postData = $_POST;
        $this->content = file_get_contents('php://input');
        $this->headers = getallheaders();
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
}
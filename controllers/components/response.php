<?php

/**
 * Description of ResponseHandlerComponent
 * @author kandrews
 */
class ResponseComponent extends Component {

    /** HTTP Response Code Constants */
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NO_CONTENT = 204;

    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_TEMPORARY_REDIRECT = 307;

    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_TIME_OUT = 408;
    const HTTP_TOO_MANY_REQUESTS = 429;

    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;

    public $components = array('Ext.Request');

    /**
     * @var RequestComponent
     */
    public $Request;

    /**
     * @var XmlHelper
     */
    public $XmlHelper;

    private $statusCode = 200;
    private $contentName = 'html';
    private $contentType = 'text/html';
    private $charset = 'UTF-8';
    private $headers = array();

    /**
     * {@inheritdoc}
     */
    public function initialize(&$controller) {

        if($this->Request->requestedWith() === 'xml') {
            App::import('Helper', 'Xml');
            $this->XmlHelper = new XmlHelper();
        }

        if($this->Request->requestedWith() === 'xml' || $this->Request->requestedWith() === 'json') {
            $this->contentName = $this->RequestHandler->requestedWith();
            $this->contentType = 'application/' . $this->Request->requestedWith();
        }

        parent::initialize($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeRedirect(&$controller, $url, $status = null) {
        parent::beforeRedirect($controller, $url, $status);
    }

    /**
     * Http Response Code
     * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes List of valid response codes
     * @param integer $code
     */
    public function setStatusCode($code)
    {
        if($this->isValidStatusCode($code)) {
            $this->statusCode = $code;
        }
        return $this;
    }

    /**
     * Set response content type headers
     * @param string $name json | xml
     * @param string $type application/json | application/xml
     * @return boolean
     */
    public function setContentType($name, $type) {

        if($this->isValidContentType($name, $type)) {
            $this->contentName = $name;
            $this->contentType = $type;
        }
        return $this;
    }

    /**
     *
     * @param string $name
     * @param string $value
     * @param boolean $replace
     */
    public function addHeader($name, $value, $replace=true) {
        if(!empty($name) && !empty($value) && (!array_key_exists($name, $this->headers) || $replace === true)) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    /**
     * Called by the controller automatically and uses private method send() to setup response
     * @param Controller $controller
     */
    public function beforeRender(&$controller) {
        $render = $this->getRenderer();

        if(isset($controller->viewVars['response'])) {
            $controller->viewVars['response'] = $render($controller->viewVars['response']);
        }

        $this->send();

        parent::beforeRender($controller);
    }

    /**
     * Setup response before rendering the view
     * Hooked into the controller beforeRender(); you don't have to run this
     */
    private function send()
    {
        http_response_code($this->statusCode);
        header($this->getContentType(), true);

        foreach ($this->getHeaders() as $header) {
            header($header);
        }
    }

    private function getRenderer()
    {
        $renderer = function($var) {
            return $var;
        };

        if($this->contentName === 'xml') {
            $xmlHelper = $this->XmlHelper;
            $renderer = function($var) use ($xmlHelper) {
                return $xmlHelper->serialize($var);
            };
        } elseif($this->contentName === 'json') {
            $renderer = function($var) {
                return json_encode($var);
            };
        }

        return $renderer;
    }

    /**
     * Returns an array of headers converted to strings
     * @return array
     */
    private function getHeaders()
    {
        $headers = array();
        foreach ($this->headers as $headerName => $headerValue) {
            $headers[] = $headerName . ': ' . $headerValue . ';';
        }
        return $headers;
    }

    private function getContentType() {
        header('Content-type: ' . $this->contentType . '; charset=' . $this->charset, true);
    }

    /**
     *
     * @param integer $code
     * @return boolean
     */
    private function isValidStatusCode($code) {

        if(!is_numeric($code) || strlen($code) !== 3) {
            return false;
        }

        $validCodes = array(
            200 => 'OK',
            201 => 'Created',
            301 => 'Moved Permanently',
            302 => 'Found',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Accepted',
        );

        return array_key_exists($code, $validCodes);
    }

    /**
     *
     * @param string $name json | xml | other
     * @param string $type application/json | application/xml | other
     * @return boolean
     */
    private function isValidContentType($name, $type) {

        $return = false;

        $validTypes = array(
            'json' => 'application/json',
            'xml' => 'application/xml',
            'html' => 'text/html',
        );

        if(array_key_exists($name, $validTypes) && $validTypes[$name] === $type) {
            $return = true;
        }

        return $return;
    }

}

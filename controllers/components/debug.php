<?php

use \DebugBar\StandardDebugBar;

/**
 * Access class for the DebugBar, makes sure there is only one instance of the DebugBar
 * Use of this class requires phpdebugbar to be loaded via composer and composer autoloading to be 
 * initialised as part of the cake front controller or bootstrap.php 
 * 
 * Also assets need to be copied from vendor/ to public_html/vendor to make js/css/imgs available to browsers.
 * 
 * @link http://phpdebugbar.com/docs/ 
 */
class DebugComponent extends Component {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 
     * @return StandardDebugBar
     */
    public function getInstance() {
        return ClassRegistry::getInstance()->getObject('debugBar');
    }
    
    /**
     * {@inheritdoc}
     */
    public function initialize(&$controller, $settings=array()) {
        $debugBar = new StandardDebugBar();
        ClassRegistry::getInstance()->addObject('debugBar', $debugBar);
        App::import('Lib', 'Ext.MysqliCollector');
        
        try {
            $this->getInstance()->getCollector('mysqli');
        } catch (Exception $ex) {
            $this->getInstance()->addCollector(new Mysqli_Collector());     
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeRedirect(&$controller, $url, $status = null, $exit = true) {
        parent::beforeRedirect($controller, $url, $status, $exit);
    }
    
    /**
     * {@inheritdoc}
     */
    public function beforeRender(&$controller)
    {
        $debugbarRenderer = $this->getInstance()->getJavascriptRenderer();
        $debugbarRenderer->setBaseUrl('/ext/');
        $controller->set('debugbarRenderer', $debugbarRenderer);
        $controller->set('debugShow', (Configure::read('PRODUCTION')) ? ((Configure::read('debug') < 2) ? false : true) : true);
        parent::beforeRender($controller);
    }
}

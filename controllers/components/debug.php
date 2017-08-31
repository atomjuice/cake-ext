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
class DebugComponent extends Component
{
    /**
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

        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $this->getInstance()['time']->addMeasure('Starting Application Bootstrap and Routing.', (float) $_SERVER['REQUEST_TIME_FLOAT'], microtime(true));
        }

        $this->getInstance()['time']->startMeasure(get_class($controller) . '_controller', 'Starting ' . get_class($controller) . '.');
        $this->getInstance()['time']->startMeasure(get_class($controller) . '_controller_beforefilter', 'Starting ' . get_class($controller) . ' beforeFilter.');

        App::import('Lib', 'Ext.MysqliCollector');
        try {
            $this->getInstance()->getCollector('mysqli');
        } catch (Exception $ex) {
            $this->getInstance()->addCollector(new Mysqli_Collector());
        }

        parent::initialize($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function startup(&$controller)
    {
        $this->getInstance()['time']->stopMeasure(get_class($controller) . '_controller_beforefilter');
        $this->getInstance()['time']->startMeasure(get_class($controller) . '_controller_action', 'Starting ' . get_class($controller) . '->' . $controller->action . ' action.');
        parent::startup($controller);
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

        $this->getInstance()['time']->stopMeasure(get_class($controller) . '_controller_action');
        $this->getInstance()['time']->startMeasure(get_class($controller) . '_controller_render', 'Starting ' . get_class($controller) . '->' . $controller->action . ' render.');
//
        $controller->set('debugbarRenderer', $debugbarRenderer);
        $controller->set('debugShow', (Configure::read('PRODUCTION')) ? ((Configure::read('debug') < 2) ? false : true) : true);
        parent::beforeRender($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(&$controller)
    {
        if($controller->autoRender) {
            $debugbarRenderer = $this->getInstance()->getJavascriptRenderer();
            echo (((Configure::read('PRODUCTION')) ? ((Configure::read('debug') < 2) ? false : true) : true)) ? $debugbarRenderer->render() : '';
        }
        parent::shutdown($controller);
    }
}

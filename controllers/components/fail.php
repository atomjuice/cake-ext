<?php

/**
 * @internal depends on Sentry
 * @author Kevin Andrews <kevin.andrews@atomjuice.com>
 */
class FailComponent extends Component
{
    /**
     * @var Raven_Client
     */
    private $client;

    /**
     * @var Controller
     */
    private $controllerName;

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
    public function init(&$controller)
    {
        $this->client = ClassRegistry::getInstance()->getObject('sentryClient');
        $this->controllerName = get_class($controller);
        parent::init($controller);
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
     * @return Raven_Client|boolean
     */
    public function getInstance()
    {
        return ($this->client instanceof Raven_Client) ? $this->client : false;
    }

    /**
     *
     * @param string $message
     * @param \Exception $e
     */
    public function fail($message, \Exception $e=false)
    {
        if($this->getInstance()) {
            if($e instanceof \Exception) {
                $this->getInstance()->captureException($e, ['tags' => ['class' => $this->controllerName]]); /** @todo get class from controller */
            } else {
                $this->getInstance()->captureMessage($message, [], ['tags' => ['class' => $this->controllerName]], true);
            }
        }
    }
}

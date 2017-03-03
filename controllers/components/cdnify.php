<?php

/**
 * Description of cdnify
 *
 * @author Kevin Andrews <kevin.andrews@atomjuice.com>
 */
class CdnifyComponent extends Component
{
    public $hasRun = false;
    public $rules = [];

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
        $this->controller =& $controller;
        parent::initialize($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function startup(&$controller)
    {
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
     * {@inheritdoc}
     */
    public function shutdown(&$controller)
    {
        if (!$this->hasRun) {
            $this->process();
        }
    }

    public function process()
    {
		if(Configure::read('CDN_ENABLED') === true && Configure::read('CDN_URL')) {
			$this->controller->output = $this->processOutput($this->controller->output, Configure::read('CDN_URL'));
		}



		$this->run = true;
	}

	public function processOutput($output, $staticBaseUrl)
    {
        $htmlDom = new voku\helper\HtmlDomParser($output);

        $linkedImages = $htmlDom->find('img');
        $linkedCssElements = $htmlDom->find('link');
        $allLinkedJavascriptElements = $htmlDom->find('script');

        /** modify locally served js files with cdn url **/
        foreach ($allLinkedJavascriptElements as $linkedJavascriptElement) {
            $attribute = $linkedJavascriptElement->getAttribute('src');
            if(strpos($attribute, '//') === false && strpos($attribute, 'http') === false && !empty($attribute)) {
                $linkedJavascriptElement->setAttribute('src', $staticBaseUrl . $attribute);
            }
        }

        foreach ($linkedCssElements as $linkedCssElement) {
            $attribute = $linkedCssElement->getAttribute('href');
            if(strpos($attribute, '//') === false && strpos($attribute, 'http') === false && $linkedCssElement->getAttribute('rel') === "stylesheet") {
                $linkedCssElement->setAttribute('href', $staticBaseUrl . $attribute);
            }
        }

        foreach ($linkedImages as $linkedImage) {
            $attribute = $linkedImage->getAttribute('src');
            if(strpos($attribute, '//') === false && strpos($attribute, 'http') === false) {
                $linkedImage->setAttribute('src', $staticBaseUrl . $attribute);
            }
        }

		return $htmlDom->html();
	}
}

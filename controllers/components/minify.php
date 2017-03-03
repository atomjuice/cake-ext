<?php

/**
 * Description of minify
 *
 * @author Kevin Andrews <kevin.andrews@atomjuice.com>
 */
class MinifyComponent extends Component
{
    public $run = false;

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
        if ($this->run===false) {
            $this->processOutput();
        }
    }

    private function processOutput()
    {
        if (Configure::read('MINIFY_HTML_OUTPUT') === true) {
            $this->controller->output = $this->minifyHtmlOutput($this->controller->output);
        }
        $this->run = true;
    }

    /**
     * Minification library call for output html.
     * @param string $output
     * @return string
     */
    public function minifyHtmlOutput($output)
    {
        $htmlMin = new voku\helper\HtmlMin();
        $htmlMin->doOptimizeAttributes();                     // optimize html attributes
        $htmlMin->doRemoveComments();                         // remove default HTML comments
        $htmlMin->doRemoveDefaultAttributes();                // remove defaults
        $htmlMin->doRemoveDeprecatedAnchorName();             // remove deprecated anchor-jump
        $htmlMin->doRemoveDeprecatedScriptCharsetAttribute(); // remove deprecated charset-attribute (the browser will use the charset from the HTTP-Header, anyway)
        $htmlMin->doRemoveDeprecatedTypeFromScriptTag();      // remove deprecated script-mime-types
        $htmlMin->doRemoveDeprecatedTypeFromStylesheetLink(); // remove "type=text/css" for css links
        $htmlMin->doRemoveEmptyAttributes();                  // remove some empty attributes
        $htmlMin->doRemoveHttpPrefixFromAttributes();         // remove optional "http:"-prefix from attributes
        $htmlMin->doRemoveValueFromEmptyInput();              // remove 'value=""' from empty <input>
        $htmlMin->doRemoveWhitespaceAroundTags(false);        // remove whitespace around tags
        $htmlMin->doSortCssClassNames();                      // sort css-class-names, for better gzip results
        $htmlMin->doSortHtmlAttributes();                     // sort html-attributes, for better gzip results
        $htmlMin->doSumUpWhitespace();                        // sum-up extra whitespace from the Dom
        return $htmlMin->minify($output);
    }
}

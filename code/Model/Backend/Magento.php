<?php

class Aoe_Static_Model_Backend_Magento extends Aoe_Static_Model_Backend_Abstract
{

    protected $_useAjax = TRUE;

    protected $_useCachedResponse = NULL;

    /**
     * Clear all cached pages
     *
     * @return void
     */
    public function flush()
    {
        Mage::app()->getCacheInstance()->cleanType('aoestatic');
    }

    /**
     * @param  $tags
     * @return void
     */
    public function cleanCache($tags)
    {
        // No additional cleaning necessary
    }

    /**
     * Save the response body in the cache before sending it.
     *
     * @param Mage_Core_Controller_Response_Http $response
     * @param  $lifetime
     * @return void
     */
    public function httpResponseSendBefore(Mage_Core_Controller_Response_Http $response, $lifetime)
    {
        // Do not overwrite cached response if response was pulled from cache or cached response
        // was invalidated by an observer of the `aoestatic_use_cached_response` event
        if ($this->getUseCachedResponse() === NULL)
        {
            $cacheKey = $this->getCacheKey();
            $tags = $this->helper()->getTags();
            $tags[] = Aoe_Static_Helper_Data::CACHE_TAG;
            Mage::app()->saveCache($response->getBody(), $cacheKey, $tags, $lifetime);
        }

        // Inject dynamic content replacement at end of body to save an Ajax request
        if( ! $this->useAjax()) {
            $body = $response->getBody('default');
            $body = str_replace('</body>', $this->getDynamicBlockReplacement().'</body>', $body, 1);
            $response->setBody($body, 'default');
        }
    }

    public function setUseCachedResponse($flag)
    {
        $this->_useCachedResponse = $flag;
    }

    public function getUseCachedResponse()
    {
        return $this->_useCachedResponse;
    }

    /**
     * This method is called by Mage_Core_Model_Cache->processRequest()
     *
     * @param  string|bool $content
     * @return bool
     */
    public function extractContent($content)
    {
        $helper = Mage::helper('aoestatic');
        if( ! $helper->isEnabled()) {
            return FALSE;
        }

        $cacheKey = $this->getCacheKey();
        if(Mage::app()->getCacheInstance()->getFrontend()->test($cacheKey)) {
            $this->setUseCachedResponse(TRUE);

            // Event allows observers to cancel the sending of a cached response
            Mage::dispatchEvent('aoestatic_use_cached_response', array(
                'backend' => $this,
            ));

            if($this->getUseCachedResponse()) {
                return Mage::app()->load($cacheKey);
            }
        }
        return FALSE;
    }

    public function getDynamicBlockReplacement()
    {
        if( ! Mage::app()->getFrontController()->getAction()) {
            $scopeCode = ''; // TODO
            $scopeType = 'store'; // TODO

            Mage::app()->init($scopeCode, $scopeType);
        }

        // Create a subrequest
        $request = new Mage_Core_Controller_Request_Http('aoestatic/call/index');
        $request->setModuleName('aoestatic');
        $request->setControllerName('call');
        $request->setActionName('index');
        $request->setControllerModule('Aoe_Static');
        // TODO $request->setParam('full_action_name', ???);
        $request->setParam('blocks', $this->getDynamicBlocks());
        if(Mage::registry('product')) {
            $request->setParam('product_id', Mage::registry('product')->getId());
        }
        $response = new Mage_Core_Controller_Response_Http;
        $controller = new Aoe_Static_CallController($request, $response);
        $controller->preDispatch();
        $controller->dispatch('index');

        return "<script type=\"text/javascript\">aoeStaticReplace({$response->getBody()});</script>";
    }
}

<?php
/**
 * This cache backend uses Magento's main cache storage for full page cache.
 *
 * Pros:
 *   - Drop-in and go, no additional requirements
 *   - Offers additional flexibility when determining if cached response should be used
 *   - Cache clearing and invalidation is handled instantly and automatically
 *   - Experimental: can do dynamic replacement without using Ajax
 * Cons:
 *   - Magento is still loaded (but, controller is only dispatched when necessary)
 *   - Will probably increase size of Magento's cache considerably
 *
 * TODO: extend this with a version which uses a separate cache backend so primary cache is not affected
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Colin Mollenhour
 */
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

        // Experimental: Inject dynamic content replacement at end of body to save an Ajax request
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
        if( ! $this->helper()->isEnabled()) {
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
                return Mage::app()->loadCache($cacheKey);
            }
        }
        return FALSE;
    }

    /**
     * Incomplete and untested.
     *
     * @return string
     */
    public function getDynamicBlockReplacement()
    {
        // Append dynamic block content to end of page to be replaced by javascript, but not Ajax
        if($dynamicBlocks = $this->helper()->getObservedBlocks()) {
            // Init store if it has not been inited yet (page served from cache)
            if( ! Mage::app()->getFrontController()->getAction()) {
                $scopeCode = ''; // TODO
                $scopeType = 'store'; // TODO

                Mage::app()->init($scopeCode, $scopeType);
            }

            // Create a subrequest
            $request = new Mage_Core_Controller_Request_Http('phone/call/index');
            $request->setModuleName('phone');
            $request->setControllerName('call');
            $request->setActionName('index');
            $request->setControllerModule('Aoe_Static');
            // TODO $request->setParam('full_action_name', ???);
            $request->setParam('blocks', $dynamicBlocks);
            $response = new Mage_Core_Controller_Response_Http;
            $controller = new Aoe_Static_CallController($request, $response);
            $controller->preDispatch();
            $controller->dispatch('index');

            return "<script type=\"text/javascript\">aoeStaticReplace({$response->getBody()});</script>";
        }

        // No dynamic blocks at this time
        else {
            return '';
        }
    }

}

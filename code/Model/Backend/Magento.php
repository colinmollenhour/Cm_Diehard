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
 * To use this backend you must add it to the cache request processors in app/etc/local.xml:
 *
 * <cache>
 *   <request_processors>
 *     <diehard>Cm_Diehard_Model_Backend_Magento</diehard>
 *   </request_processors>
 *   ...
 * </cache>
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
class Cm_Diehard_Model_Backend_Magento extends Cm_Diehard_Model_Backend_Abstract
{

    protected $_name = 'Magento';

    protected $_useAjax = TRUE;

    protected $_useCachedResponse = NULL;

    /**
     * Clear all cached pages
     *
     * @return void
     */
    public function flush()
    {
        Mage::app()->getCacheInstance()->cleanType('diehard');
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
        // was invalidated by an observer of the `diehard_use_cached_response` event
        if ($this->getUseCachedResponse() === NULL)
        {
            $cacheKey = $this->getCacheKey();
            $tags = $this->helper()->getTags();
            $tags[] = Cm_Diehard_Helper_Data::CACHE_TAG;
            Mage::app()->saveCache($response->getBody(), $cacheKey, $tags, $lifetime);
        }

        // Experimental: Inject dynamic content replacement at end of body to save an Ajax request
        if( ! $this->useAjax()) {
            $body = $response->getBody('default');
            $bodyPos = strrpos($body, '</body>');
            if($bodyPos && ($dynamic = $this->getDynamicBlockReplacement())) {
              $body = substr_replace($body, $dynamic, $bodyPos, 0);
            }
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
        $cacheKey = $this->getCacheKey();
        if(Mage::app()->getCacheInstance()->getFrontend()->test($cacheKey)) {
            $this->setUseCachedResponse(TRUE);

            // TODO - allow external code to cancel the sending of a cached response

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
            $request = new Mage_Core_Controller_Request_Http('_diehard/load/ajax');
            $request->setModuleName('_diehard');
            $request->setControllerName('load');
            $request->setActionName('ajax');
            $request->setControllerModule('Cm_Diehard');
            // TODO $request->setParam('full_action_name', ???);
            // TODO Reset layout
            // TODO Disable cache and re-enable after render
            $request->setParam('blocks', $dynamicBlocks);
            $response = new Mage_Core_Controller_Response_Http;
            $controller = new Cm_Diehard_LoadController($request, $response);
            $controller->preDispatch();
            $controller->dispatch('ajax');

            return "<script type=\"text/javascript\">Diehard.replaceBlocks({$response->getBody()});</script>";
        }

        // No dynamic blocks at this time
        else {
            return '';
        }
    }

}

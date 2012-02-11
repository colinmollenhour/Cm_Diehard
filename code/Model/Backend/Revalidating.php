<?php
/**
 * This backend uses HTTP/1.1 headers to tell the receiver to cache the page, but revalidate the cache.
 * The revalidation is done within Magento to allow for advanced invalidation (e.g. cookies).
 *
 * ETags are used to communicate if the cached content is stale so make sure your proxy
 * respects the ETag value.
 *
 * Weak ETags are used since byte-range requests are not supported.
 *
 * To use this backend you must add it to the cache request processors in app/etc/local.xml:
 *
 * <cache>
 *   <request_processors>
 *     <diehard>Cm_Diehard_Model_Backend_Revalidating</diehard>
 *   </request_processors>
 *   ...
 * </cache>
 *
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
abstract class Cm_Diehard_Model_Backend_Revalidating extends Cm_Diehard_Model_Backend_Abstract
{

    protected $_name = 'Proxy';

    protected $_useAjax = TRUE;
    
    /**
     * Clear all cached pages (clears etags)
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
     * When caching a page simply generate and cache a random value as the ETag
     *
     * @param Mage_Core_Controller_Response_Http $response
     * @param $lifetime
     */
    public function httpResponseSendBefore(Mage_Core_Controller_Response_Http $response, $lifetime)
    {
        $useEtags = Mage::getStoreConfigFlag('system/diehard/use_etags');
        if($useEtags) {
            $cacheData = sha1(microtime().mt_rand());
        } else {
            $cacheData = $this->_rfc1123Date();
        }

        $cacheKey = $this->getCacheKey();
        $tags = $this->helper()->getTags();
        $tags[] = Cm_Diehard_Helper_Data::CACHE_TAG;
        Mage::app()->saveCache($cacheData, $cacheKey, $tags, $lifetime);

        // Set headers so the page is cached with the ETag/Last-Modified value for invalidation
        $cacheControl = sprintf(Mage::getStoreConfig('system/diehard/cachecontrol'), $lifetime);
        $response->setHeader('Cache-Control', $cacheControl, true);
        // TODO - Set Expires?
        if($useEtags) {
            $response->setHeader('ETag', 'W/"'.$cacheData.'"', true);
        } else {
            $response->setHeader('Last-Modified', $cacheData, true);
        }
    }

    /**
     * This method is called by Mage_Core_Model_Cache->processRequest()
     *
     * If the request is a revalidate request (If-None-Match) then compare the value with the cached
     * ETag value and either reply 304 Not Modified or 200 (with rendered page).
     *
     * @param  string|bool $content
     * @return bool
     */
    public function extractContent($content)
    {
        if( ! $this->helper()->isEnabled()) {
            return FALSE;
        }

        // Use ETags if given
        if(
             ($ifNoneMatch = Mage::app()->getRequest()->getHeader('If-None-Match'))
          && ($etag = Mage::app()->loadCache($this->getCacheKey()))
          && preg_match('|(?:W/)?"'.$etag.'"|', $ifNoneMatch)
        ) {
            // Client's cached content is valid, we're all done here!
            Mage::app()->getResponse()->setHttpResponseCode(304);
            Mage::app()->getResponse()->setHeader('ETag', 'W/"'.$etag.'"', true);
            return TRUE;
        }

        // Fall-back to Last-Modified if given
        if(
             ($ifModifiedSince = Mage::app()->getRequest()->getHeader('If-Modified-Since'))
          && ($lastModified = Mage::app()->loadCache($this->getCacheKey()))
          && $lastModified == $ifModifiedSince
        ) {
            // Client's cached content is valid, we're all done here!
            Mage::app()->getResponse()->setHttpResponseCode(304);
            Mage::app()->getResponse()->setHeader('Last-Modified', $this->_rfc1123Date(), true);
            return TRUE;
        }

        return FALSE;
    }

    protected function _rfc1123Date()
    {
        return gmdate('D, d M Y H:i:s', time()).' GMT';
    }

}

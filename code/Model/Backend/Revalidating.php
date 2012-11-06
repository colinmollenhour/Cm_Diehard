<?php
/**
 * This backend uses HTTP/1.1 headers to tell the receiver to cache the page, but revalidate the cache.
 * The revalidation is done within Magento to allow for advanced invalidation (e.g. cookies).
 *
 * ETag or Last-Modified headers are used to communicate if the cached content is stale so make sure
 * your proxy supports revalidation and choose the proper method. Weak ETags are used since byte-range
 * requests are not supported. Nginx does not support ETag as of 1.2.
 *
 * Pros:
 *   - Off-loads storage of cache to remote server.
 *   - Requires no invalidation on the remote server since every request is revalidated.
 *   - Can be used with the browser's cache for easy testing.
 *   - Allows advanced caching decisions.
 *   - You can use third-party services for your cache frontend and still have instant invalidation.
 * Cons:
 *   - Every request will still hit PHP, but the cache hit will be much more efficient than a miss.
 *
 * Reverse-proxy servers that support revalidation:
 *   - Squid (ETag w/ Vary supported)
 *   - Nginx (No ETag support)
 *   - Apache (ETag support buggy, possibly fixed in 2.4)
 *   - Varnish (Possible ETag support in 2.0.5)
 *   - Apache TrafficServer (ETag supported, unknown to what degree)
 *
 * Third-party services that definitely support revalidation:
 *   - Cloudfront
 *
 * Third-party services that probably support revalidation (unconfirmed):
 *   - Akamai
 *   - Limelight
 *   - EdgeCast
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
class Cm_Diehard_Model_Backend_Revalidating extends Cm_Diehard_Model_Backend_Abstract
{

    protected $_name = 'Revalidating';

    /* Supported methods: */
    protected $_useAjax = TRUE;
    protected $_useEsi  = TRUE;
    protected $_useJs   = FALSE;

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
        $cacheKey = $this->getCacheKey();

        // Use existing cache data if it exists in case there are multiple upstream proxies
        // If a record exists then any content generated at the time the record was is assumed to not be stale
        if ( ! ($cacheData = Mage::app()->loadCache($cacheKey))) {
            if($useEtags) {
                $cacheData = sha1(microtime().mt_rand());
            } else {
                $cacheData = $this->_rfc1123Date();
            }
            $tags = $this->helper()->getTags();
            $tags[] = Cm_Diehard_Helper_Data::CACHE_TAG;
            Mage::app()->saveCache($cacheData, $cacheKey, $tags, $lifetime);
        }

        // Set headers so the page is cached with the ETag/Last-Modified value for invalidation
        $cacheControl = sprintf(Mage::getStoreConfig('system/diehard/cachecontrol'), $lifetime);
        $response->setHeader('Cache-Control', $cacheControl, true);
        $response->setHeader('Expires', $this->_rfc1123Date(time() + $lifetime), true);
        if($useEtags) {
            $response->setHeader('ETag', 'W/"'.$cacheData.'"', true);
        } else {
            $response->setHeader('Last-Modified', $cacheData, true);
        }
    }

    /**
     * This method is called by Mage_Core_Model_Cache->processRequest()
     *
     * If the request is a revalidate request (If-None-Match or If-Modified-Since) then compare the
     * value with the cached value and either reply 304 Not Modified or 200 (with rendered page).
     *
     * @param  string|bool $content
     * @return bool
     */
    public function extractContent($content)
    {
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

    /**
     * @param null|int $time
     * @return string
     */
    protected function _rfc1123Date($time = NULL)
    {
        if ($time === NULL) {
            $time = time();
        }
        return gmdate('D, d M Y H:i:s', $time).' GMT';
    }

}

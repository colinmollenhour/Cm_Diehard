<?php
/**
 * This backend assumes the use of a reverse proxy and dumps urls to invalidate out to
 * plain text files so that cache clearing can happen externally.
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
abstract class Cm_Diehard_Model_Backend_Proxy extends Cm_Diehard_Model_Backend_Abstract
{

    const CACHE_TAG = 'DIEHARD_URLS';
    const PREFIX_TAG = 'DIEHARD_URLS_';
    const PREFIX_KEY = 'URL_';

    protected $_name = 'Proxy';

    protected $_useAjax = TRUE;
    
    public function httpResponseSendBefore(Mage_Core_Controller_Response_Http $response, $lifetime)
    {
        // Cache the url with all of the related tags (prefixed)
        $url = $this->helper()->getCurrentUrl();
        $cacheKey = $this->getCacheKey();
        $tags = $this->_getCacheTags($this->helper()->getTags());
        $tags[] = self::CACHE_TAG;
        Mage::app()->saveCache($url, self::PREFIX_KEY.$cacheKey, $tags, $lifetime);

        // Set a header so the page is cached
        $cacheControl = sprintf(Mage::getStoreConfig('system/diehard/cachecontrol'), $lifetime);
        $response->setHeader('Cache-Control', $cacheControl, true);
    }

    public function cleanCache($tags)
    {
        // Get all urls related to the tags being cleaned
        $tags = $this->_getCacheTags($tags);
        $ids = Mage::app()->getCache()->getBackend()->getIdsMatchingAnyTags($tags);
        $urls = array();
        foreach($ids as $id) {
            if($url = Mage::app()->loadCache($id)) {
                $urls[] = $url;
            }
        }
        Mage::app()->getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);

        // TODO - clean urls from cache storage
    }

    public function flush()
    {
        // TODO - clean all urls, clean self::CACHE_TAG cache
    }

    protected function _getCacheTags($tags)
    {
        $saveTags = array();
        foreach($tags as $tag) {
            $saveTags[] = self::PREFIX_TAG.$tag;
        }
        return $saveTags;
    }
}

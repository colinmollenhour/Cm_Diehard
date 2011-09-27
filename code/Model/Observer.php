<?php
/**
 * Observer model
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 * @author      Colin Mollenhour
 */
class Aoe_Static_Model_Observer
{

    /** @return Aoe_Static_Helper_Data */
    public function helper()
    {
        return Mage::helper('aoestatic');
    }

    /**
     * Check when caching should be enabled. Caching can still be disabled
     * before the response is sent.
     *
     * @param Varien_Event_Observer $observer
     */
    public function processPreDispatch(Varien_Event_Observer $observer)
    {
        if($this->helper()->isEnabled() && Mage::app()->getRequest()->isGet()) {
            Mage::register('aoestatic', $this->helper());

            $fullActionName = $observer->getControllerAction()->getFullActionName();

            $lifetime = $this->helper()->isCacheableAction($fullActionName);
            if ($lifetime)
            {
                // Set current request as cacheable for the given lifetime
                $this->helper()->setLifetime($lifetime);

                // Set default ignored blocks for new sessions
                if( $this->helper()->getIgnoredBlocks() === NULL) {
                    $this->helper()->addDefaultIgnoredBlocks();
                }
            }
        }
    }

    /**
     * If caching is enabled let the backend take additional actions (set headers, cache content, etc.)
     *
     * @param Varien_Event_Observer $observer
     */
    public function httpResponseSendBefore(Varien_Event_Observer $observer)
    {
        if($this->helper()->isEnabled()) {
            $fullActionName = $observer->getControllerAction()->getFullActionName();
            $lifetime = $this->helper()->getLifetime();
            $response = $observer->getControllerAction()->getResponse(); /* @var $response Mage_Core_Controller_Response_Http */

            if($lifetime) {
                // Allow backend to take action on responses that are to be cached
                $this->helper()->getBackend()->httpResponseSendBefore($response, $lifetime);
            }

            // Update ignored blocks cookie
            $ignored = (array) $this->helper()->getIgnoredBlocks();
            $addedIgnored = $this->helper()->getAddedIgnoredBlocks();
            $removedIgnored = $this->helper()->getRemovedIgnoredBlocks();
            $ignored = array_unique(array_merge($ignored, $addedIgnored));
            $ignored = array_diff($ignored, $removedIgnored);
            $this->helper()->setIgnoredBlocks($ignored);

            // Add debug data
            if($this->helper()->isDebug()) {
                $response->setHeader('X-FullPageCache', "$fullActionName-$lifetime", true);
                $response->setHeader('X-FullPageCache-Tags', implode('|', $this->helper()->getTags()), true);
                $response->setHeader('X-FullPageCache-AddedIgnoredBlocks', implode('|',$addedIgnored), true);
                $response->setHeader('X-FullPageCache-RemovedIgnoredBlocks', implode('|',$removedIgnored), true);
            }
        }
    }

    /**
     * Observe all cleaned cache tags to purge cached pages.
     *
     * This event runs after the cache is cleaned so if a backend needs to take action on some
     * cache data before it is cleaned it must keep a separate cache storage.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function applicationCleanCache(Varien_Event_Observer $observer)
    {
        if($this->helper()->isEnabled()) {
            $tags = $observer->getTags();
            $this->helper()->getBackend()->cleanCache($tags);
        }
    }

    /**
     * Observe all models so that a cached page can be associated with all model instances
     * loaded in the course of page rendering.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function modelLoadAfter(Varien_Event_Observer $observer)
    {
        if($this->helper()->getLifetime() && ($tags = $observer->getObject()->getCacheTags())) {
            // add tags to list of tags for this page
            $this->helper()->addTags($tags);
        }
    }
}

<?php
/**
 * Observer model
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
class Cm_Diehard_Model_Observer
{

    /** @return Cm_Diehard_Helper_Data */
    public function helper()
    {
        return Mage::helper('diehard');
    }

    /**
     * Check when caching should be enabled. Caching can still be disabled
     * before the response is sent.
     *
     * @param Varien_Event_Observer $observer
     */
    public function controllerActionPredispatch(Varien_Event_Observer $observer)
    {
        if (Mage::registry('diehard')) {
            return;
        }
        Mage::register('diehard', $this->helper());
        if ($this->helper()->isEnabled() && Mage::app()->getRequest()->isGet())
        {
            $fullActionName = $observer->getControllerAction()->getFullActionName();

            $lifetime = $this->helper()->isCacheableAction($fullActionName);
            if ($lifetime)
            {
                // Set current request as cacheable for the given lifetime
                $this->helper()->setLifetime($lifetime);
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
            $response = $observer->getResponse(); /* @var $response Mage_Core_Controller_Response_Http */
            $fullActionName = $this->helper()->getFullActionName();

            Mage::dispatchEvent('diehard_response_send_before_'.strtolower($this->helper()->getBackend()->getName()), array(
              'response' => $response,
              'full_action_name' => $fullActionName,
              'lifetime' => $this->helper()->getLifetime(),
            ));

            $lifetime = $this->helper()->getLifetime();
            if($lifetime !== FALSE) { // 0 lifetime allowed (e.g. max-age=0)
                // Allow backend to take action on responses that are to be cached
                $this->helper()->getBackend()->httpResponseSendBefore($response, $lifetime);
            }

            // Update ignored blocks cookie
            $ignored = (array) $this->helper()->getIgnoredBlocks();
            if ($ignored === NULL) {
              $ignored = $this->helper()->getDefaultIgnoredBlocks();
            }
            $addedIgnored = $this->helper()->getAddedIgnoredBlocks();
            $removedIgnored = $this->helper()->getRemovedIgnoredBlocks();
            $ignored = array_unique(array_merge($ignored, $addedIgnored));
            $ignored = array_diff($ignored, $removedIgnored);
            $this->helper()->setIgnoredBlocks($ignored);

            // Add debug data
            if($this->helper()->isDebug()) {
                $response->setHeader('X-Diehard', "{$this->helper()->getBackend()->getName()}-$fullActionName-$lifetime", true);
                $response->setHeader('X-Diehard-Tags', implode('|', $this->helper()->getTags()), true);
                $response->setHeader('X-Diehard-Blocks-Added', implode('|',$addedIgnored), true);
                $response->setHeader('X-Diehard-Blocks-Removed', implode('|',$removedIgnored), true);
            }

            // Log miss to counter
            $counter = new Cm_Diehard_Helper_Counter;
            $counter->logRequest($fullActionName, FALSE);
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
        if($this->helper()->getLifetime() && ($tags = $observer->getObject()->getCacheIdTags())) {
            // add tags to list of tags for this page
            $this->helper()->addTags($tags);
        }
    }
}

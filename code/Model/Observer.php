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
            if ($lifetime) {
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
            $fullActionName = $observer->getControllerAction()->getFullActionName();
            $lifetime = $this->helper()->getLifetime();

            /* @var $response Mage_Core_Controller_Response_Http */
            $response = $observer->getControllerAction()->getResponse();

            if($lifetime) {
                $this->helper()->getBackend()->httpResponseSendBefore($response, $lifetime);
            }
            if($this->helper()->isDebug()) {
                $response->setHeader('X-FullPageCache', "$fullActionName-$lifetime", true);
                $response->setHeader('X-FullPageCache-Tags', implode('|', $this->helper()->getTags()), true);
            }
        }
    }

    /**
     * Observe all cleaned cache tags to purge cached pages.
     *
     * TODO - this will not work since cache is already cleaned before this point...
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function applicationCleanCache(Varien_Event_Observer $observer)
    {
        $tags = $observer->getTags();
        $this->helper()->getBackend()->cleanCache($tags);
    }

    public function modelLoadAfter(Varien_Event_Observer $observer)
    {
        if($this->helper()->getLifetime() && ($tags = $observer->getObject()->getCacheTags())) {
            // add tags to list of tags for this page
            $this->helper()->addTags($tags);
        }
    }
}

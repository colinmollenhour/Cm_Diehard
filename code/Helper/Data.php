<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 * @author      Colin Mollenhour
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{

    const CACHE_TAG = 'AOESTATIC';

    protected $_lifetime = FALSE;

    protected $_tags = array();

    protected $_blocks = array();

    /**
     * @return bool
     */
    public function isEnabled()
    {
        static $enabled = NULL;
        if($enabled === NULL) {
            $enabled = Mage::app()->useCache('aoestatic');
        }
        return $enabled;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return Mage::getStoreConfigFlag('system/aoe_static/debug');
    }

    /**
     * @return bool|int
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * @param  bool|int $lifetime
     * @return void
     */
    public function setLifetime($lifetime)
    {
        $this->_lifetime = $lifetime;
    }

    /**
     * Add tags to the list of tags to associate with this request.
     * Duplicate tags will be filtered after all tags are added.
     *
     * @param array $tags
     * @return void
     */
    public function addTags(array $tags)
    {
        $this->_tags = array_merge($this->_tags, $tags);
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return array_unique($this->_tags);
    }

    /**
     * @param string $htmlId
     * @param string $nameInLayout
     * @return void
     */
    public function addBlock($htmlId, $nameInLayout)
    {
        $this->_blocks[$htmlId] = $nameInLayout;
    }

    /**
     * @return array
     */
    public function getBlocks()
    {
        return $this->_blocks;
    }

    /**
     * Check if a fullActionName is configured as cacheable
     *
     * @param string $fullActionName
     * @return false|int false if not cacheable, otherwise lifetime in seconds
     */
    public function isCacheableAction($fullActionName)
    {
        $cacheActionsString = Mage::getStoreConfig('system/aoe_static/cache_actions');
        foreach (explode(',', $cacheActionsString) as $singleActionConfiguration) {
            list($actionName, $lifeTime) = explode(';', $singleActionConfiguration);
            if (trim($actionName) == $fullActionName) {
                return intval(trim($lifeTime));
            }
        }
        return false;
	}

    /**
     * @return string
     */
    public function getBackendModel()
    {
        $backend = Mage::getStoreConfig('system/aoe_static/backend');
        return (string) Mage::getConfig()->getNode('aoestatic/backends/'.$backend.'/model');
    }

    /**
     * @return string
     */
    public function getBackendLabel()
    {
        $backend = Mage::getStoreConfig('system/aoe_static/backend');
        return (string) Mage::getConfig()->getNode('aoestatic/backends/'.$backend.'/label');
    }

    /**
     * @return Aoe_Static_Model_Backend_Abstract
     */
    public function getBackend()
    {
        return Mage::getSingleton($this->getBackendModel());
    }

    /**
     * @return string|NULL
     */
    public function getJslib()
    {
        return Mage::getStoreConfig('system/aoe_static/jslib');
    }

    /**
     * @return bool
     */
    public function useAjax()
    {
        return $this->getBackend()->useAjax() && $this->getJsLib();
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->getBackend()->flush();
    }

}

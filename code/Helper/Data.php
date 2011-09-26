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

    const XML_PATH_BACKEND              = 'system/aoe_static/backend';
    const XML_PATH_DEBUG                = 'system/aoe_static/debug';
    const XML_PATH_JSLIB                = 'system/aoe_static/jslib';

    const CACHE_TAG = 'AOESTATIC';

    protected $_lifetime = FALSE;

    protected $_tags = array();

    protected $_blocks = array();

    protected $_addedIgnoredBlocks = array();

    protected $_removedIgnoredBlocks = array();

    /**
     * @return bool
     */
    public function isEnabled()
    {
        static $enabled = NULL;
        if($enabled === NULL) {
            $enabled = Mage::app()->useCache('aoestatic') && Mage::getStoreConfig(self::XML_PATH_BACKEND);
        }
        return $enabled;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEBUG);
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
    public function addDynamicBlock($htmlId, $nameInLayout)
    {
        $this->_blocks[$htmlId] = $nameInLayout;
    }

    /**
     * @return array
     */
    public function getDynamicBlocks()
    {
        return $this->_blocks;
    }

    /**
     * @param string $block
     * @return void
     */
    public function addIgnoredBlock($block)
    {
        if($block instanceof Mage_Core_Block_Abstract) {
            $block = $block->getNameInLayout();
        }
        $this->_addedIgnoredBlocks[] = $block;
    }

    /**
     * @param string $block
     * @return void
     */
    public function removeIgnoredBlock($block)
    {
        if($block instanceof Mage_Core_Block_Abstract) {
            $block = $block->getNameInLayout();
        }
        $this->_removeIgnoredBlocks[] = $block;
    }

    /**
     * @return array
     */
    public function getAddedIgnoredBlocks()
    {
        return $this->_addedIgnoredBlocks;
    }

    /**
     * @return array
     */
    public function getRemovedIgnoredBlocks()
    {
        return $this->_removedIgnoredBlocks;
    }

    /**
     * @return array
     */
    public function getObservedBlocks()
    {
        return array_diff($this->_addedIgnoredBlocks, $this->_removedIgnoredBlocks);
    }

    /**
     * Check if a fullActionName is configured as cacheable
     *
     * @param string $fullActionName
     * @return false|int false if not cacheable, otherwise lifetime in seconds
     */
    public function isCacheableAction($fullActionName)
    {
        $lifeTime = Mage::app()->getConfig()->getNode('aoestatic/actions/'.$fullActionName);
        if($lifeTime) {
            return intval($lifeTime);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getBackendModel()
    {
        $backend = Mage::getStoreConfig(self::XML_PATH_BACKEND);
        return (string) Mage::getConfig()->getNode('aoestatic/backends/'.$backend.'/model');
    }

    /**
     * @return string
     */
    public function getBackendLabel()
    {
        $backend = Mage::getStoreConfig(self::XML_PATH_BACKEND);
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
        return Mage::getStoreConfig(self::XML_PATH_JSLIB);
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

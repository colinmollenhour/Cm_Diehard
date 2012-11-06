<?php
/**
 * Data helper
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
class Cm_Diehard_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_BACKEND              = 'system/diehard/backend';
    const XML_PATH_DEBUG                = 'system/diehard/debug';
    const XML_PATH_JSLIB                = 'system/diehard/jslib';

    const CACHE_TAG = 'DIEHARD';

    /** Cookie key for list of ignored blocks */
    const COOKIE_IGNORED_BLOCKS = 'diehard_ignored';

    const COOKIE_CACHE_KEY_DATA = 'dh';

    protected $_lifetime = FALSE;

    protected $_tags = array();

    protected $_blocks = array();

    protected $_addedIgnoredBlocks = array();

    protected $_removedIgnoredBlocks = array();

    protected $_params = array();

    /**
     * @return bool
     */
    public function isEnabled()
    {
        static $enabled = NULL;
        if($enabled === NULL) {
            $enabled = Mage::app()->useCache('diehard') && Mage::getStoreConfig(self::XML_PATH_BACKEND);
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
        Mage::unregister('diehard_lifetime');
        if($lifetime = (int)$lifetime) {
            Mage::register('diehard_lifetime', $lifetime);
        }
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
     * @param string|Mage_Core_Block_Abstract $block
     * @return void
     */
    public function addIgnoredBlock($block)
    {
        if ($block instanceof Mage_Core_Block_Abstract) {
            $block = $block->getNameInLayout();
        }
        $this->_addedIgnoredBlocks[] = $block;
    }

    /**
     * @param string|Mage_Core_Block_Abstract $block
     * @return void
     */
    public function removeIgnoredBlock($block)
    {
        if ($block instanceof Mage_Core_Block_Abstract) {
            $block = $block->getNameInLayout();
        }
        $this->_removedIgnoredBlocks[] = $block;
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
     * Add list of ignored blocks from config for fresh sessions
     *
     * @return void
     */
    public function addDefaultIgnoredBlocks()
    {
        foreach(Mage::getConfig()->getNode('diehard/ignored')->asArray() as $block => $_) {
            $this->addIgnoredBlock($block);
        }
    }

    /**
     * Get the ignored blocks for the current session (cookie value)
     *
     * @return array|null
     */
    public function getIgnoredBlocks()
    {
        $ignoredBlocks = Mage::getSingleton('core/cookie')->get(self::COOKIE_IGNORED_BLOCKS);
        return ($ignoredBlocks === false ? NULL : explode(',', $ignoredBlocks));
    }

    /**
     * Set the ignored blocks for the current session (cookie value)
     *
     * @param array $ignoredBlocks
     * @return void
     */
    public function setIgnoredBlocks($ignoredBlocks)
    {
        $ignoredBlocks = implode(',', $ignoredBlocks);
        Mage::getSingleton('core/cookie')->set(self::COOKIE_IGNORED_BLOCKS, $ignoredBlocks);
    }

    /**
     * Get array of all blocks excluding the ignored blocks
     *
     * @return array of htmlId => nameInLayout
     */
    public function getObservedBlocks()
    {
        $blocks = array_values($this->getDynamicBlocks());
        $ignored = (array) $this->getIgnoredBlocks();
        $ignored = array_merge($ignored, $this->_addedIgnoredBlocks);
        $ignored = array_diff($ignored, $this->_removedIgnoredBlocks);
        $blocks = array_diff($blocks, $ignored);

        $observedBlocks = array();
        foreach($this->getDynamicBlocks() as $htmlId => $nameInLayout) {
            if(in_array($nameInLayout, $blocks)) {
                $observedBlocks[$htmlId] = $nameInLayout;
            }
        }
        return $observedBlocks;
    }

    /**
     * Get all dynamic params that were set
     *
     * @return array
     */
    public function getDynamicParams()
    {
        return $this->_params;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Cm_Diehard_Helper_Data
     */
    public function setParam($key, $value)
    {
        if ($value instanceof Varien_Object) {
            $value = $value->getData();
        }
        $this->_params[$key] = $value;
        return $this;
    }

    /**
     * Check if a fullActionName is configured as cacheable
     *
     * @param string $fullActionName
     * @return bool|int false if not cacheable, otherwise lifetime in seconds
     */
    public function isCacheableAction($fullActionName)
    {
        $lifeTime = Mage::app()->getConfig()->getNode('diehard/actions/'.$fullActionName);
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
        return (string) Mage::getConfig()->getNode('diehard/backends/'.$backend.'/model');
    }

    /**
     * @return string
     */
    public function getBackendLabel()
    {
        $backend = Mage::getStoreConfig(self::XML_PATH_BACKEND);
        return (string) Mage::getConfig()->getNode('diehard/backends/'.$backend.'/label');
    }

    /**
     * @return Cm_Diehard_Model_Backend_Abstract
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
     * @return bool
     */
    public function useEsi()
    {
        return $this->getBackend()->useEsi() && $this->getJsLib();
    }

    /**
     * @return bool
     */
    public function useJs()
    {
        return $this->getBackend()->useJs() && $this->getJsLib();
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->getBackend()->flush();
    }

}

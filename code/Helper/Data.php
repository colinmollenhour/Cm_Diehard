<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * @return bool
     */
    public function isEnabled()
    {
        static $enabled = NULL;
        if($enabled === NULL) {
          $enabled = $this->isModuleOutputEnabled();
        }
        return $enabled;
    }

    /**
     * Returns true if the current request will be cached
     *
     * @param null|bool $flag
     * @return bool
     */
    public function isForCache($flag = NULL)
    {
        static $cached = FALSE;
        if($flag !== NULL) {
            $cached = $flag;
        }
        return $cached;
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
     * @return string|NULL
     */
    public function getJslib()
    {
        return Mage::getStoreConfig('system/aoe_static/jslib');
    }

}

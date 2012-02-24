<?php
/**
 * Abstract backend driver model
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
abstract class Cm_Diehard_Model_Backend_Abstract
{

    protected $_name = '';

    protected $_cacheKey;

    /**
     * If true, JS will be inserted to fetch dynamic content via Ajax
     * 
     * @var bool
     */
    protected $_useAjax;

    /**
     * @return Cm_Diehard_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('diehard');
    }

    /**
     * Set lazily and only once
     *
     * @return string
     */
    public function getCacheKey()
    {
        if( ! $this->_cacheKey) {
            $this->_cacheKey = implode('_', array(
                'DIEHARD',
                $this->_name,
                // Mage::app()->getStore()->getId(), // Can't be used before init
                Mage::app()->getRequest()->getScheme(),
                Mage::app()->getRequest()->getHttpHost(FALSE),
                Mage::app()->getRequest()->getRequestUri(),
                Mage::app()->getRequest()->getCookie(Cm_Diehard_Helper_Data::COOKIE_CACHE_KEY_DATA, '')
                // Design?
            ));
        }
        return $this->_cacheKey;
    }

    /**
     * @return bool
     */
    public function useAjax()
    {
        return $this->_useAjax;
    }
    
    abstract public function flush();

    abstract public function cleanCache($tags);

    abstract public function httpResponseSendBefore(Mage_Core_Controller_Response_Http $response, $lifetime);

}

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
            $cacheKeyInfo = array(
                'DIEHARD',
                $this->_name,
                Mage::app()->getStore()->getId(),
                Mage::app()->getRequest()->getScheme(),
                Mage::app()->getRequest()->getHttpHost(FALSE),
                Mage::app()->getRequest()->getRequestUri(),
                // Design?
            );
            // TODO - need some method besides events for allowing other modules to add cache key info
            $this->_cacheKey = implode('_', $cacheKeyInfo);
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

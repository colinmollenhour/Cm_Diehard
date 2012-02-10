<?php
/**
 * Abstract backend driver model
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
abstract class Cm_Diehard_Model_Backend_Abstract
{

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
     * @return string
     */
    public function getCacheKey()
    {
        return implode('_',array(
            'DIEHARD',
            Mage::app()->getStore()->getId(),
            Mage::app()->getRequest()->getScheme(),
            Mage::app()->getRequest()->getHttpHost(FALSE),
            Mage::app()->getRequest()->getRequestUri(),
            // Design?
        ));
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

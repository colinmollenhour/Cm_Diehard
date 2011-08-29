<?php
/**
 * Abstract backend driver model
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Colin Mollenhour
 */
abstract class Aoe_Static_Model_Backend_Abstract
{

    /**
     * If true, JS will be inserted to fetch dynamic content via Ajax
     * 
     * @var bool
     */
    protected $_useAjax;

    /**
     * @return Aoe_Static_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('aoestatic');
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return implode('_',array(
            'AOESTATIC',
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

<?php
/**
 * Abstract backend driver model
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
abstract class Cm_Diehard_Model_Backend_Abstract
{

    const INJECTION_JS   = 'js';
    const INJECTION_AJAX = 'ajax';
    const INJECTION_ESI  = 'esi';

    protected $_name = '';

    protected $_cacheKey;

    /**
     * If true, the backend supports Ajax dynamic block replacement (should be all)
     * 
     * @var bool
     */
    protected $_useAjax;

    /**
     * If true, the backend supports ESI for dynamic block replacement (should be all)
     *
     * @var bool
     */
    protected $_useEsi;

    /**
     * If true, the backend supports inline javascript for dynamic block replacement (only backends that do not use a caching proxy)
     *
     * @var bool
     */
    protected $_useJs;

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
     * @return string
     */
    public function getInjectionMethod()
    {
        return Mage::getStoreConfig('system/diehard/injection');
    }

    /**
     * @return bool
     */
    public function useAjax()
    {
        if ($this->getInjectionMethod() == self::INJECTION_AJAX) {
            if ( ! $this->_useAjax) {
                Mage::throwException('Ajax injection method is not supported by the selected backend.');
            }
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * @return bool
     */
    public function useEsi()
    {
        if ($this->getInjectionMethod() == self::INJECTION_ESI) {
            if ( ! $this->_useEsi) {
                Mage::throwException('ESI injection method is not supported by the selected backend.');
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @return bool
     */
    public function useJs()
    {
        if ($this->getInjectionMethod() == self::INJECTION_JS) {
            if ( ! $this->_useJs) {
                Mage::throwException('Javascript injection method is not supported by the selected backend.');
            }
            return TRUE;
        }
        return FALSE;
    }

    /**
     * @param string $body
     * @return array|bool
     */
    public function extractParamsFromBody($body)
    {
        if ( ! preg_match('|<!-- ###DIEHARD:(.+)### -->|', $body, $matches)) {
            return FALSE;
        }
        return json_decode($matches[1], true);
    }

    /**
     * @param string $body
     * @param string $replace
     * @return string
     */
    public function replaceParamsInBody($body, $replace)
    {
        return preg_replace('|<!-- ###DIEHARD:(.+)### -->|', $replace, $body, 1);
    }

    abstract public function flush();

    abstract public function cleanCache($tags);

    abstract public function httpResponseSendBefore(Mage_Core_Controller_Response_Http $response, $lifetime);

}

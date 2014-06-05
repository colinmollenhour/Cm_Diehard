<?php

/**
 * Beforebodyend block
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Block_Beforebodyend extends Mage_Core_Block_Template
{

    /**
     * @return null|Cm_Diehard_Helper_Data
     */
    public function diehard()
    {
        return Mage::registry('diehard');
    }

    /**
     * @return array
     */
    public function getDynamicParamsCombined()
    {
        $params = array();
        $params['full_action_name'] = $this->getAction()->getFullActionName();
        $params['blocks'] = $this->diehard()->getDynamicBlocks();
        $params['params'] = $this->diehard()->getDynamicParams();
        $params['default_ignored_blocks'] = $this->diehard()->getDefaultIgnoredBlocks();
        if ( ! $params['blocks']) {
            $params['blocks'] = new stdClass;
        }
        if ( ! $params['params']) {
            $params['params'] = new stdClass;
        }
        return $params;
    }

    /**
     * @return string
     */
    public function getLoadAjaxUrl()
    {
        $params = array();
        if(Mage::app()->getStore()->isCurrentlySecure()) {
            $params['_secure'] = TRUE;
        }
        return Mage::getUrl('_diehard/load/json', $params);
    }

    /**
     * @return string
     */
    public function getLoadEsiUrl()
    {
        $params = array();
        if(Mage::app()->getStore()->isCurrentlySecure()) {
            $params['_secure'] = TRUE;
        }
        $params['json'] = json_encode($this->getDynamicParamsCombined());
        return Mage::getUrl('_diehard/load/esi', $params);
    }

}

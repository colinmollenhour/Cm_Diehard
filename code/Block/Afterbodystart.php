<?php

/**
 * after_body_start block
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Block_Afterbodystart extends Mage_Core_Block_Template
{

    /**
     * @return string
     */
    public function getLoadAjaxUrl()
    {
        $params = array();
        if(Mage::app()->getStore()->isCurrentlySecure()) {
            $params['_secure'] = TRUE;
        }
        return Mage::getUrl('_diehard/load/ajax', $params);
    }

}

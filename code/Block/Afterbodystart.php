<?php

/**
 * after_body_start block
 *
 * @author Colin Mollenhour
 */
class Aoe_Static_Block_Afterbodystart extends Mage_Core_Block_Template
{

    /**
     * @return string
     */
    public function getPhoneUrl()
    {
        $params = array();
        if(Mage::app()->getStore()->isCurrentlySecure()) {
            $params['_secure'] = TRUE;
        }
        return Mage::getUrl('phone/call/index', $params);
    }

}

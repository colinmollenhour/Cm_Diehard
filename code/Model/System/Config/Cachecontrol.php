<?php

/**
 *
 * @author Colin Mollenhour
 */
class Aoe_Static_Model_System_Config_Cachecontrol {

    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('aoestatic')->__('Proxy cache only'),
                'value' => 'public, must-revalidate, s-maxage=%d',
            ),
            array(
                'label' => Mage::helper('aoestatic')->__('Client-side cache allowed'),
                'value' => 'max-age=%d',
            ),
        );
    }

}

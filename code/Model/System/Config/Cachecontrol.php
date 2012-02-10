<?php

/**
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Model_System_Config_Cachecontrol {

    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('diehard')->__('Proxy cache only'),
                'value' => 'public, must-revalidate, s-maxage=%d',
            ),
            array(
                'label' => Mage::helper('diehard')->__('Client-side cache allowed'),
                'value' => 'max-age=%d',
            ),
        );
    }

}

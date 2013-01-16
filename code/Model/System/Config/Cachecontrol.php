<?php
/**
 * List Cache-Control header presets
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Model_System_Config_Cachecontrol {

    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('diehard')->__('Proxy cache only'),
                'value' => 'public, no-cache="set-cookie", s-maxage=%d',
            ),
            array(
                'label' => Mage::helper('diehard')->__('Proxy cache only, must-revalidate'),
                'value' => 'public, no-cache="set-cookie", must-revalidate, s-maxage=%d',
            ),
            array(
                'label' => Mage::helper('diehard')->__('Client-side cache allowed'),
                'value' => 'private, max-age=%d',
            ),
            array(
                'label' => Mage::helper('diehard')->__('Client-side cache allowed, must-revalidate'),
                'value' => 'private, must-revalidate, max-age=%d',
            ),
        );
    }

}

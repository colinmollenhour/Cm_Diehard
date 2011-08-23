<?php

/**
 *
 * @author Colin Mollenhour
 */
class Aoe_Static_Model_System_Config_Jslib {

    public function toOptionArray()
    {
        return array(
            array('value' => 'jquery',    'label' => Mage::helper('aoestatic')->__('jQuery')),
            array('value' => 'prototype', 'label' => Mage::helper('aoestatic')->__('Prototype')),
            array('value' => '',          'label' => Mage::helper('aoestatic')->__('None')),
        );
    }

}

<?php
/**
 * List available dynamic content injection methods.
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Model_System_Config_Injection {

    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('diehard')->__('Javascript'),
                'value' => 'js',
            ),
            array(
                'label' => Mage::helper('diehard')->__('Ajax'),
                'value' => 'ajax',
            ),
            array(
                'label' => Mage::helper('diehard')->__('ESI'),
                'value' => 'esi',
            ),
        );
    }

}

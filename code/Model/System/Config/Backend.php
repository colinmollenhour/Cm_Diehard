<?php

/**
 *
 * @author Colin Mollenhour
 */
class Aoe_Static_Model_System_Config_Backend {

    public function toOptionArray()
    {
        $options = array();
        foreach(Mage::getConfig()->getNode('aoestatic/backends') as $backend) {
            $options[] = array(
                'label' => Mage::helper('aoestatic')->__($backend->label),
                'value' => $backend->getName()
            );
        }
        return $options;
    }

}

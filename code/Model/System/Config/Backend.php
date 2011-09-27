<?php

/**
 *
 * @author Colin Mollenhour
 */
class Aoe_Static_Model_System_Config_Backend {

    public function toOptionArray()
    {
        $options = array();
        foreach(Mage::getConfig()->getNode('aoestatic/backends')->children() as $backend) {
            $options[] = array(
                'label' => Mage::helper('aoestatic')->__((string) $backend->label),
                'value' => $backend->getName()
            );
        }
        return $options;
    }

}

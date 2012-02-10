<?php

/**
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Model_System_Config_Backend {

    public function toOptionArray()
    {
        $options = array();
        foreach(Mage::getConfig()->getNode('diehard/backends')->children() as $backend) {
            $options[] = array(
                'label' => Mage::helper('diehard')->__((string) $backend->label),
                'value' => $backend->getName()
            );
        }
        return $options;
    }

}

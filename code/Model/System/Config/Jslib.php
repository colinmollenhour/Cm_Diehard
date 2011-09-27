<?php

/**
 *
 * @author Colin Mollenhour
 */
class Aoe_Static_Model_System_Config_Jslib {

    public function toOptionArray()
    {
        $options = array();
        foreach( Mage::getConfig()->getNode('aoestatic/jslibs')->children() as $data) {
            $options[] = array('value' => (string) $data->path, 'label' => (string) $data->label);
        }
        return $options;
    }

}

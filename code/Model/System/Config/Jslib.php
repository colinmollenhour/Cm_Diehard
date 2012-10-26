<?php
/**
 * List available javascript libraries
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_Model_System_Config_Jslib {

    public function toOptionArray()
    {
        $options = array();
        foreach( Mage::getConfig()->getNode('diehard/jslibs')->children() as $data) {
            $options[] = array('value' => (string) $data->path, 'label' => (string) $data->label);
        }
        return $options;
    }

}

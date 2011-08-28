<?php

class Aoe_Static_Model_Cache extends Mage_Core_Model_Cache
{

    /**
     * @return bool
     */
    public function flush()
    {
        if(Mage::helper('aoestatic')->getBackendType() != 'aoestatic/magento') {
            Mage::helper('aoestatic')->flush();
        }
        return parent::flush();
    }

    /**
     * @param string $typeCode
     * @return Mage_Core_Model_Cache
     */
    public function cleanType($typeCode)
    {
        if($typeCode == 'aoestatic' && Mage::helper('aoestatic')->getBackendType() != 'aoestatic/magento') {
            Mage::helper('aoestatic')->flush();
        }
        return parent::cleanType($typeCode);
    }

}

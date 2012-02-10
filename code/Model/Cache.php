<?php

class Cm_Diehard_Model_Cache extends Mage_Core_Model_Cache
{

    /**
     * @return bool
     */
    public function flush()
    {
        if(Mage::helper('diehard')->getBackendType() != 'diehard/magento') {
            Mage::helper('diehard')->flush();
        }
        return parent::flush();
    }

    /**
     * @param string $typeCode
     * @return Mage_Core_Model_Cache
     */
    public function cleanType($typeCode)
    {
        if($typeCode == 'diehard' && Mage::helper('diehard')->getBackendType() != 'diehard/magento') {
            Mage::helper('diehard')->flush();
        }
        return parent::cleanType($typeCode);
    }

}

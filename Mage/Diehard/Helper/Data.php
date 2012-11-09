<?php

/**
 * In the off-chance that Magento tries to load the diehard helper when the module is disabled due to a cache or mistake..
 */
class Mage_Diehard_Helper_Data extends Cm_Diehard_Helper_Data
{

    /**
     * @return bool
     */
    public function isEnabled()
    {
        Mage::logException(new Exception('Hey, Cm_Diehard is supposed to be disabled.. Yet, here I am!'));
        return FALSE;
    }

    /**
     * @return bool|int
     */
    public function getLifetime()
    {
        Mage::logException(new Exception('Hey, Cm_Diehard is supposed to be disabled.. Yet, here I am!'));
        return FALSE;
    }

}

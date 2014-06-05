<?php

class Cm_Diehard_Helper_Counter
{

    /** @var \Mage_Core_Model_Config_Element */
    protected $config;

    public function __construct(Mage_Core_Model_Config_Element $config)
    {
        $this->config = $config;
    }

    /**
     * Log a hit or a miss to Redis.
     *
     * @param string $fullActionName
     * @param bool $hit
     */
    public function logRequest($fullActionName, $hit)
    {
        try {
            $redis = new Credis_Client($this->config->server, NULL, 0.1);
            $redis->pipeline();
            if ($this->config->db) {
                $redis->select($this->config->db);
            }
            $redis->incr('diehard:'.($hit ? 'hit':'miss'));
            if ($fullActionName && $this->config->is('full_action_name')) {
                $redis->incr('diehard:'.$fullActionName.':'.($hit ? 'hit':'miss'));
            }
            $redis->exec();
            $redis->close();
        }
        catch (Exception $e) {
            Mage::log($e->getMessage(), Zend_Log::ERR, 'diehard_counter.log');
        }
    }

}

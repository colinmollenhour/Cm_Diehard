<?php

class Cm_Diehard_Helper_Counter
{

  /**
   * Log a hit or a miss to Redis.
   *
   * @param string $fullActionName
   * @param bool $hit
   */
  public function logRequest($fullActionName, $hit)
  {
      $config = Mage::getConfig()->getNode('global/diehard/counter');
      if ( ! $config || ! $config->is('enabled')) {
          return;
      }

      try {
          $redis = new Credis_Client($config->server, NULL, 0.1);
          $redis->pipeline();
          if ($config->db) {
              $redis->select($config->db);
          }
          $redis->incr('diehard:'.($hit ? 'hit':'miss'));
          if ($fullActionName && $config->is('full_action_name')) {
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

<?php

/**
 * Beforebodyend block
 *
 * @author Fabrizio Branca
 */
class Aoe_Static_Block_Beforebodyend extends Mage_Core_Block_Template {



	/**
	 * Send headers to tell varnish whether this page should be cached or not
	 *
	 * @return void
	 */
	public function sendHeaders() {
		$fullActionName = $this->getFullActionName();
		$lifeTime = $this->isCacheableAction($fullActionName);
		$response = Mage::app()->getResponse();
		$response->setHeader('Magento_Lifetime', $lifeTime, true); // Only for debugging and information
		$response->setHeader('Magento_Action', $fullActionName, true); // Only for debugging and information
		if ($lifeTime) {
			$response->setHeader('Cache-Control', 'max-age='.$lifeTime, true);
			// $response->setHeader('Pragma', 'public', true);
			$response->setHeader('aoestatic', 'cache', true);
		}
	}



	/**
	 * Check if a fullActionName is configured as cacheable
	 *
	 * @param string $fullActionName
	 * @return false|int false if not cacheable, otherwise lifetime in seconds
	 */
	public function isCacheableAction($fullActionName) {
		$cacheActionsString = Mage::getStoreConfig('system/aoe_static/cache_actions');
		foreach (explode(',', $cacheActionsString) as $singleActionConfiguration) {
			list($actionName, $lifeTime) = explode(';', $singleActionConfiguration);
			if (trim($actionName) == $fullActionName) {
				return intval(trim($lifeTime));
			}
		}
		return false;
	}



	/**
	 * Get full action name
	 *
	 * @return string
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 */
	public function getFullActionName() {
		$request = Mage::app()->getRequest();
		return $request->getRequestedRouteName().'_'.
			$request->getRequestedControllerName().'_'.
			$request->getRequestedActionName();
	}

}
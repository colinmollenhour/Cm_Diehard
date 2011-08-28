<?php

/**
 * CallController
 * Renders the block that are requested via an ajax call
 *
 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
 */
class Aoe_Static_CallController extends Mage_Core_Controller_Front_Action {



	/**
	 * Index action. This action is called by an ajax request
	 *
	 * @return void
	 * @author Fabrizio Branca <fabrizio.branca@aoemedia.de>
	 */
	public function indexAction() {

		// if (!$this->getRequest()->isXmlHttpRequest()) { Mage::throwException('This is not an XmlHttpRequest'); }

		$response = array();
		$response['sid'] = Mage::getModel('core/session')->getEncryptedSessionId();

		// Translate JSON to params if using Prototype
		if($params = $this->getRequest()->getParam('json')) {
          $params = json_decode($params, TRUE);
          $this->getRequest()->setParams($params);
        }

        // TODO - implement as event observer
		if ($currentProductId = $this->getRequest()->getParam('product_id')) {
			Mage::getSingleton('catalog/session')->setLastViewedProductId($currentProductId);
		}

		$handles = array(
			'default',
			'AOESTATIC_default',
			'AOESTATIC_'.$this->getRequest()->getParam('full_action_name')
		);
		$this->loadLayout($handles);
		$layout = $this->getLayout();

		$requestedBlockNames = $this->getRequest()->getParam('blocks', array());
		foreach ($requestedBlockNames as $id => $requestedBlockName) {
			$tmpBlock = $layout->getBlock($requestedBlockName);
			if ($tmpBlock) {
				$response['blocks'][$id] = $tmpBlock->toHtml();
			} else {
				$response['blocks'][$id] = 'BLOCK NOT FOUND';
			}
		}
		$this->getResponse()->setBody(Zend_Json::encode($response));
	}

}

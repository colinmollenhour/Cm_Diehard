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

		if ($currentProductId = $this->getRequest()->getParam('currentProductId')) {
			Mage::getSingleton('catalog/session')->setLastViewedProductId($currentProductId);
		}

		$this->loadLayout();
		$layout = $this->getLayout();

		$requestedBlockNames = $this->getRequest()->getParam('getBlocks');
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

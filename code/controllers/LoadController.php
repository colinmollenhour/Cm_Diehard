<?php

/**
 * CallController
 * Renders the block that are requested via an ajax call
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_CallController extends Mage_Core_Controller_Front_Action
{

    /**
     * Index action. This action is called by an ajax request
     */
    public function ajaxAction() {

        // if (!$this->getRequest()->isXmlHttpRequest()) { Mage::throwException('This is not an XmlHttpRequest'); }

        $response = array(
            'blocks' => array(),
            'ignoreBlocks' => array(),
        );

        // Translate JSON to params if using Prototype
        if($params = $this->getRequest()->getParam('json')) {
          $params = json_decode($params, TRUE);
          $this->getRequest()->setParams($params);
        }

        // Add handles to layout
        $handles = array(
            'default',
            'DIEHARD_default',
            'DIEHARD_'.$this->getRequest()->getParam('full_action_name')
        );
        $this->loadLayout($handles);
        $layout = $this->getLayout();

        // Render block content
        $requestedBlockNames = $this->getRequest()->getParam('blocks', array());
        foreach ($requestedBlockNames as $id => $requestedBlockName) {
            $tmpBlock = $layout->getBlock($requestedBlockName);
            if ($tmpBlock) {
                $response['blocks'][$id] = $tmpBlock->toHtml();
            } else {
                $response['blocks'][$id] = '<!-- BLOCK NOT FOUND -->';
            }
        }

        // Send JSON response
        $this->getResponse()->setBody(Zend_Json::encode($response));
    }

}

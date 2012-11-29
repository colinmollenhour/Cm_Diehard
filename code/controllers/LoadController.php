<?php

/**
 * CallController
 * Renders the block that are requested via an ajax call
 *
 * @author Colin Mollenhour
 */
class Cm_Diehard_LoadController extends Mage_Core_Controller_Front_Action
{

    /**
     * Get an object with all of the block contents
     *
     * @return array
     */
    protected function _getResponseObject()
    {
        // Disable caching mode temporarily
        $helper = Mage::helper('diehard'); /* @var $helper Cm_Diehard_Helper_Data */
        $oldLifetime = $helper->getLifetime();
        $helper->setLifetime(FALSE);

        $response = array(
            'blocks' => array(),
            'ignoreBlocks' => array(),
        );

        // Translate JSON params to top-level params
        if ($params = $this->getRequest()->getParam('json')) {
            $params = json_decode($params, TRUE);
            $this->getRequest()->setParams($params);
        }

        // Translate "params" to top-level params
        if ($params = $this->getRequest()->getParam('params')) {
            $this->getRequest()->setParams($params);
        }

        // Add handles to layout
        $handles = array(
            'DIEHARD_default',
            'DIEHARD_'.$this->getRequest()->getParam('full_action_name')
        );
        $this->loadLayout($handles);
        $layout = $this->getLayout();

        // Render all blocks contents
        if ($this->getRequest()->getParam('all_blocks')) {
            foreach ($this->getLayout()->getAllBlocks() as $block) { /* @var $block Mage_Core_Block_Abstract */
                $htmlId = $block->getDiehardSelector();
                $response['blocks'][$htmlId] = $block->toHtml();
            }
        }

        // When using Ajax the client can specify a subset of available blocks
        else {
            $requestedBlockNames = $this->getRequest()->getParam('blocks', array());
            foreach ($requestedBlockNames as $htmlId => $requestedBlockName) {
                $tmpBlock = $layout->getBlock($requestedBlockName);
                if ($tmpBlock) {
                    $response['blocks'][$htmlId] = $tmpBlock->toHtml();
                } else {
                    $response['blocks'][$htmlId] = '<!-- BLOCK NOT FOUND -->';
                }
            }
        }

        // Restore caching mode
        $helper->setLifetime($oldLifetime);

        return $response;
    }

    /**
     * Send the response as a JSON object
     */
    public function jsonAction()
    {
        $response = $this->_getResponseObject();
        $this->getResponse()->setBody(Zend_Json::encode($response));
    }

    /**
     * Send the response as JSONP (JSON wrapped in a callback, safe for Cross-Domain requests)
     */
    public function jsonpAction()
    {
        $response = $this->_getResponseObject();
        $this->getResponse()->setBody('Diehard.replaceBlocks('.Zend_Json::encode($response).');');
    }

    /**
     * Send the response in a format suitable for ESI injection.
     */
    public function esiAction()
    {
        $response = $this->_getResponseObject();
        $this->getResponse()->setBody(
            '<script type="text/javascript">Diehard.replaceBlocks('.Zend_Json::encode($response).');</script>'
        );
    }

}

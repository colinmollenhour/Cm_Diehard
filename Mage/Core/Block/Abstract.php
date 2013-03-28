<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Base Content Block class
 *
 * For block generation you must define Data source class, data source class method,
 * parameters array and block template
 *
 * @category   Mage
 * @package    Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * Magic methods added by Cm_Diehard:
 * @method bool getBlockIsDynamic()
 * @method bool getSuppressOutput()
 * @method Mage_Core_Block_Abstract setSuppressOutput(bool $flag)
 */
abstract class Mage_Core_Block_Abstract extends Varien_Object
{
    const CACHE_GROUP = 'block_html';
    /**
     * Block name in layout
     *
     * @var string
     */
    protected $_nameInLayout;

    /**
     * Parent layout of the block
     *
     * @var Mage_Core_Model_Layout
     */
    protected $_layout;

    /**
     * Parent block
     *
     * @var Mage_Core_Block_Abstract
     */
    protected $_parent;

    /**
     * Short alias of this block to be refered from parent
     *
     * @var string
     */
    protected $_alias;

    /**
     * Suffix for name of anonymous block
     *
     * @var string
     */
    protected $_anonSuffix;

    /**
     * Contains references to child block objects
     *
     * @var array
     */
    protected $_children = array();

    /**
     * Sorted children list
     *
     * @var array
     */
    protected $_sortedChildren = array();

    /**
     * Children blocks HTML cache array
     *
     * @var array
     */
    protected $_childrenHtmlCache = array();

    /**
     * Arbitrary groups of child blocks
     *
     * @var array
     */
    protected $_childGroups = array();

    /**
     * Request object
     *
     * @var Zend_Controller_Request_Http
     */
    protected $_request;

    /**
     * Messages block instance
     *
     * @var Mage_Core_Block_Messages
     */
    protected $_messagesBlock = null;

    /**
     * Whether this block was not explicitly named
     *
     * @var boolean
     */
    protected $_isAnonymous = false;

    /**
     * Parent block
     *
     * @var Mage_Core_Block_Abstract
     */
    protected $_parentBlock;

    /**
     * Block html frame open tag
     * @var string
     */
    protected $_frameOpenTag;

    /**
     * Block html frame close tag
     * @var string
     */
    protected $_frameCloseTag;

    protected static $_urlModel;


    /**
     * Internal constructor, that is called from real constructor
     *
     * Please override this one instead of overriding real __construct constructor
     *
     */
    protected function _construct()
    {
        /**
         * Please override this one instead of overriding real __construct constructor
         */
    }

    /**
     * Retrieve request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        $controller = Mage::app()->getFrontController();
        if ($controller) {
            $this->_request = $controller->getRequest();
        } else {
            throw new Exception(Mage::helper('core')->__("Can't retrieve request object"));
        }
        return $this->_request;
    }

    /**
     * Retrieve parent block
     *
     * @return Mage_Core_Block_Abstract
     */
    public function getParentBlock()
    {
        return $this->_parentBlock;
    }

    /**
     * Set parent block
     *
     * @param   Mage_Core_Block_Abstract $block
     * @return  Mage_Core_Block_Abstract
     */
    public function setParentBlock(Mage_Core_Block_Abstract $block)
    {
        $this->_parentBlock = $block;
        return $this;
    }

    /**
     * Retrieve current action object
     *
     * @return Mage_Core_Controller_Varien_Action
     */
    public function getAction()
    {
        return Mage::app()->getFrontController()->getAction();
    }

    /**
     * Set layout object
     *
     * @param   Mage_Core_Model_Layout $layout
     * @return  Mage_Core_Block_Abstract
     */
    public function setLayout(Mage_Core_Model_Layout $layout)
    {
        $this->_layout = $layout;
        Mage::dispatchEvent('core_block_abstract_prepare_layout_before', array('block' => $this));
        $this->_prepareLayout();
        Mage::dispatchEvent('core_block_abstract_prepare_layout_after', array('block' => $this));
        return $this;
    }

    /**
     * Preparing global layout
     *
     * You can redefine this method in child classes for changin layout
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        return $this;
    }

    /**
     * Retrieve layout object
     *
     * @return Mage_Core_Model_Layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * Check if block is using auto generated (Anonymous) name
     * @return bool
     */
    public function getIsAnonymous()
    {
        return $this->_isAnonymous;
    }

    public function setIsAnonymous($flag)
    {
        $this->_isAnonymous = $flag;
        return $this;
    }

    public function getAnonSuffix()
    {
        return $this->_anonSuffix;
    }

    public function setAnonSuffix($suffix)
    {
        $this->_anonSuffix = $suffix;
        return $this;
    }

    public function getBlockAlias()
    {
        return $this->_alias;
    }

    public function setBlockAlias($alias)
    {
        $this->_alias = $alias;
        return $this;
    }

    /**
     * Set block's name in layout and unsets previous link if such exists.
     *
     * @param $name
     * @return Mage_Core_Block_Abstract
     */
    public function setNameInLayout($name)
    {
        if (!empty($this->_nameInLayout) && $this->getLayout()) {
            $this->getLayout()->unsetBlock($this->_nameInLayout)
                ->setBlock($name, $this);
        }
        $this->_nameInLayout = $name;
        return $this;
    }

    public function getSortedChildren()
    {
        return $this->_sortedChildren;
    }

    /**
     * Set block attribute value
     *
     * Wrapper for method "setData"
     *
     * @param   string $name
     * @param   mixed $value
     * @return  Mage_Core_Block_Abstract
     */
    public function setAttribute($name, $value=null)
    {
        return $this->setData($name, $value);
    }

    /**
     * Set child block
     *
     * @param   string $name
     * @param   Mage_Core_Block_Abstract $block
     * @return  Mage_Core_Block_Abstract
     */
    public function setChild($alias, $block)
    {
        if (is_string($block)) {
            $block = $this->getLayout()->getBlock($block);
        }
        if (!$block) {
            return $this;
        }

        if ($block->getIsAnonymous()) {
            $suffix = $block->getAnonSuffix();
            if (empty($suffix)) {
                $suffix = 'child'.sizeof($this->_children);
            }
            $blockName = $this->getNameInLayout().'.'.$suffix;

            if ($this->getLayout()) {
                $this->getLayout()->unsetBlock($block->getNameInLayout())
                    ->setBlock($blockName, $block);
            }

            $block->setNameInLayout($blockName);
            $block->setIsAnonymous(false);

            if (empty($alias)) {
                $alias = $blockName;
            }
        }

        $block->setParentBlock($this);
        $block->setBlockAlias($alias);
        $this->_children[$alias] = $block;
        return $this;
    }

    /**
     * Unset child block
     *
     * @param   string $name
     * @return  Mage_Core_Block_Abstract
     */
    public function unsetChild($alias)
    {
        if (isset($this->_children[$alias])) {
            unset($this->_children[$alias]);
        }

        if (!empty($this->_sortedChildren)) {
            $key = array_search($alias, $this->_sortedChildren);
            if ($key!==false) {
                unset($this->_sortedChildren[$key]);
            }
        }

        return $this;
    }

    /**
     * Call a child and unset it, if callback matched result
     *
     * $params will pass to child callback
     * $params may be array, if called from layout with elements with same name, for example:
     * ...<foo>value_1</foo><foo>value_2</foo><foo>value_3</foo>
     *
     * Or, if called like this:
     * ...<foo>value_1</foo><bar>value_2</bar><baz>value_3</baz>
     * - then it will be $params1, $params2, $params3
     *
     * It is no difference anyway, because they will be transformed in appropriate way.
     *
     * @param string $alias
     * @param string $callback
     * @param mixed $result
     * @param array $params
     * @return Mage_Core_Block_Abstract
     */
    public function unsetCallChild($alias, $callback, $result, $params)
    {
        $child = $this->getChild($alias);
        if ($child) {
            $args     = func_get_args();
            $alias    = array_shift($args);
            $callback = array_shift($args);
            $result   = (string)array_shift($args);
            if (!is_array($params)) {
                $params = $args;
            }

            if ($result == call_user_func_array(array(&$child, $callback), $params)) {
                $this->unsetChild($alias);
            }
        }
        return $this;
    }

    /**
     * Unset all children blocks
     *
     * @return Mage_Core_Block_Abstract
     */
    public function unsetChildren()
    {
        $this->_children = array();
        $this->_sortedChildren = array();
        return $this;
    }

    /**
     * Retrieve child block by name
     *
     * @param  string $name
     * @return mixed
     */
    public function getChild($name='')
    {
        if (''===$name) {
            return $this->_children;
        } elseif (isset($this->_children[$name])) {
            return $this->_children[$name];
        }
        return false;
    }

    /**
     * Retrieve child block HTML
     *
     * @param   string $name
     * @param   boolean $useCache
     * @return  string
     */
    public function getChildHtml($name='', $useCache=true, $sorted=false)
    {
        if ('' === $name) {
            if ($sorted) {
                $children = array();
                foreach ($this->getSortedChildren() as $childName) {
                    $children[$childName] = $this->getLayout()->getBlock($childName);
                }
            } else {
                $children = $this->getChild();
            }
            $out = '';
            foreach ($children as $child) {
                $out .= $this->_getChildHtml($child->getBlockAlias(), $useCache);
            }
            return $out;
        } else {
            return $this->_getChildHtml($name, $useCache);
        }
    }

    public function getChildChildHtml($name, $childName = '', $useCache = true, $sorted = false)
    {
        if (empty($name)) {
            return '';
        }
        $child = $this->getChild($name);
        if (!$child) {
            return '';
        }
        return $child->getChildHtml($childName, $useCache, $sorted);
    }

    /**
     * Obtain sorted child blocks
     *
     * @return array
     */
    public function getSortedChildBlocks()
    {
        $children = array();
        foreach ($this->getSortedChildren() as $childName) {
            $children[$childName] = $this->getLayout()->getBlock($childName);
        }
        return $children;
    }

    /**
     * Retrieve child block HTML
     *
     * @param   string $name
     * @param   boolean $useCache
     * @return  string
     */
    protected function _getChildHtml($name, $useCache=true)
    {
        if ($useCache && isset($this->_childrenHtmlCache[$name])) {
            return $this->_childrenHtmlCache[$name];
        }

        $child = $this->getChild($name);

        if (!$child) {
            $html = '';
        } else {
            $this->_beforeChildToHtml($name, $child);
            $html = $child->toHtml();
        }

        $this->_childrenHtmlCache[$name] = $html;
        return $html;
    }

    /**
     * Prepare child block before generate html
     *
     * @param   string $name
     * @param   Mage_Core_Block_Abstract $child
     */
    protected function _beforeChildToHtml($name, $child)
    {
    }

    /**
     * Retrieve block html
     *
     * @param   string $name
     * @return  string
     */
    public function getBlockHtml($name)
    {
        if (!($layout = $this->getLayout()) && !($layout = Mage::app()->getFrontController()->getAction()->getLayout())) {
            return '';
        }
        if (!($block = $layout->getBlock($name))) {
            return '';
        }
        return $block->toHtml();
    }

    /**
     * Insert child block
     *
     * @param   Mage_Core_Block_Abstract|string $block
     * @param   string $siblingName
     * @param   boolean $after
     * @param   string $alias
     * @return  object $this
     */
    public function insert($block, $siblingName='', $after=false, $alias='')
    {
        if (is_string($block)) {
            $block = $this->getLayout()->getBlock($block);
        }
        if (!$block) {
            /*
             * if we don't have block - don't throw exception because
             * block can simply removed using layout method remove
             */
            //Mage::throwException(Mage::helper('core')->__('Invalid block name to set child %s: %s', $alias, $block));
            return $this;
        }
        if ($block->getIsAnonymous()) {
            $this->setChild('', $block);
            $name = $block->getNameInLayout();
        } elseif ('' != $alias) {
            $this->setChild($alias, $block);
            $name = $block->getNameInLayout();
        } else {
            $name = $block->getNameInLayout();
            $this->setChild($name, $block);
        }

        if (''===$siblingName) {
            if ($after) {
                array_push($this->_sortedChildren, $name);
            } else {
                array_unshift($this->_sortedChildren, $name);
            }
        } else {
            $key = array_search($siblingName, $this->_sortedChildren);
            if (false!==$key) {
                if ($after) {
                    $key++;
                }
                array_splice($this->_sortedChildren, $key, 0, $name);
            } else {
                if ($after) {
                    array_push($this->_sortedChildren, $name);
                } else {
                    array_unshift($this->_sortedChildren, $name);
                }
            }
        }

        return $this;
    }

    /**
     * Append child block
     *
     * @param   Mage_Core_Block_Abstract|string $block
     * @param   string $alias
     * @return  Mage_Core_Block_Abstract
     */
    public function append($block, $alias='')
    {
        $this->insert($block, '', true, $alias);
        return $this;
    }

    /**
     * Make sure specified block will be registered in the specified child groups
     *
     * @param string $groupName
     * @param Mage_Core_Block_Abstract $child
     */
    public function addToChildGroup($groupName, Mage_Core_Block_Abstract $child)
    {
        if (!isset($this->_childGroups[$groupName])) {
            $this->_childGroups[$groupName] = array();
        }
        if (!in_array($child->getBlockAlias(), $this->_childGroups[$groupName])) {
            $this->_childGroups[$groupName][] = $child->getBlockAlias();
        }
    }

    /**
     * Add self to the specified group of parent block
     *
     * @param string $groupName
     * @return Mage_Core_Block_Abstract
     */
    public function addToParentGroup($groupName)
    {
        $this->getParentBlock()->addToChildGroup($groupName, $this);
        return $this;
    }

    /**
     * Get a group of child blocks
     *
     * Returns an array of <alias> => <block>
     * or an array of <alias> => <callback_result>
     * The callback currently supports only $this methods and passes the alias as parameter
     *
     * @param string $groupName
     * @param string $callback
     * @param bool $skipEmptyResults
     * @return array
     */
    public function getChildGroup($groupName, $callback = null, $skipEmptyResults = true)
    {
        $result = array();
        if (!isset($this->_childGroups[$groupName])) {
            return $result;
        }
        foreach ($this->getSortedChildBlocks() as $block) {
            $alias = $block->getBlockAlias();
            if (in_array($alias, $this->_childGroups[$groupName])) {
                if ($callback) {
                    $row = $this->$callback($alias);
                    if (!$skipEmptyResults || $row) {
                        $result[$alias] = $row;
                    }
                } else {
                    $result[$alias] = $block;
                }

            }
        }
        return $result;
    }

    /**
     * Get a value from child block by specified key
     *
     * @param string $alias
     * @param string $key
     * @return mixed
     */
    public function getChildData($alias, $key = '')
    {
        $child = $this->getChild($alias);
        if ($child) {
            return $child->getData($key);
        }
    }

    /**
     * Before rendering html, but after trying to load cache
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeToHtml()
    {
        return $this;
    }

    /**
     * Specify block output frame tags
     *
     * @param $openTag
     * @param $closeTag
     * @return Mage_Core_Block_Abstract
     */
    public function setFrameTags($openTag, $closeTag=null)
    {
        $this->_frameOpenTag = $openTag;
        if ($closeTag) {
            $this->_frameCloseTag = $closeTag;
        } else {
            $this->_frameCloseTag = '/'.$openTag;
        }
        return $this;
    }

    /**
     * Produce and return block's html output
     *
     * It is a final method, but you can override _toHmtl() method in descendants if needed
     *
     * @return string
     */
    final public function toHtml()
    {
        Mage::dispatchEvent('core_block_abstract_to_html_before', array('block' => $this));
        if (Mage::getStoreConfig('advanced/modules_disable_output/'.$this->getModuleName())) {
            return '';
        }

        /* START: Added by Cm_Diehard */
        // if setBlockIsDynamic and setSuppressOutput then we automatically render only a placeholder tag.
        if ($this->getBlockIsDynamic() && Mage::registry('diehard_lifetime')) {
          if ($this->getSuppressOutput()) {
            return '<div id="'.substr($this->getDiehardSelector(),1).'"></div>';
          } else if (FALSE /* TODO - backend uses inline replacement */) {
            return '<!-- DIEHARD_BLOCK|'.$this->getDiehardSelector().'|'.json_encode($this->getCacheKeyInfo()).' -->';
          }
        }
        /* END: Added by Cm_Diehard */

        $html = $this->_loadCache();
        if (!$html) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            if ($this->hasData('translate_inline')) {
                $translate->setTranslateInline($this->getData('translate_inline'));
            }

            $this->_beforeToHtml();
            $html = $this->_toHtml();
            $this->_saveCache($html);

            if ($this->hasData('translate_inline')) {
                $translate->setTranslateInline(true);
            }
        }
        $html = $this->_afterToHtml($html);

        /**
         * Check framing options
         */
        if ($this->_frameOpenTag) {
            $html = '<'.$this->_frameOpenTag.'>'.$html.'<'.$this->_frameCloseTag.'>';
        }

        /**
         * Use single transport object instance for all blocks
         */
        static $transport;
        if ($transport === null) {
            $transport = new Varien_Object;
        }
        $transport->setHtml($html);
        Mage::dispatchEvent('core_block_abstract_to_html_after', array('block' => $this, 'transport' => $transport));
        $html = $transport->getHtml();

        return $html;
    }

    /**
     * Processing block html after rendering
     *
     * @param   string $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        return $html;
    }

    /**
     * Override this method in descendants to produce html
     *
     * @return string
     */
    protected function _toHtml()
    {
        return '';
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    protected function _getUrlModelClass()
    {
        return 'core/url';
    }

    /**
     * Enter description here...
     *
     * @return Mage_Core_Model_Url
     */
    protected function _getUrlModel()
    {
        return Mage::getModel($this->_getUrlModelClass());;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route='', $params=array())
    {
        return $this->_getUrlModel()->getUrl($route, $params);
    }

    /**
     * Generate base64-encoded url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrlBase64($route='', $params=array())
    {
        return Mage::helper('core')->urlEncode($this->getUrl($route, $params));
    }

    /**
     * Generate url-encoded url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrlEncoded($route = '', $params = array())
    {
        return Mage::helper('core')->urlEncode($this->getUrl($route, $params));
    }

    /**
     * Retrieve url of skins file
     *
     * @param   string $file path to file in skin
     * @param   array $params
     * @return  string
     */
    public function getSkinUrl($file=null, array $params=array())
    {
        return Mage::getDesign()->getSkinUrl($file, $params);
    }

    /**
     * Retrieve messages block
     *
     * @return Mage_Core_Block_Messages
     */
    public function getMessagesBlock()
    {
        if (is_null($this->_messagesBlock)) {
            return $this->getLayout()->getMessagesBlock();
        }
        return $this->_messagesBlock;
    }

    /**
     * Set messages block
     *
     * @param   Mage_Core_Block_Messages $block
     * @return  Mage_Core_Block_Abstract
     */
    public function setMessagesBlock(Mage_Core_Block_Messages $block)
    {
        $this->_messagesBlock = $block;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param string $type
     * @return Mage_Core_Block_Abstract
     */
    public function getHelper($type)
    {
        return $this->getLayout()->getBlockSingleton($type);
        //return $this->helper($type);
    }

    /**
     * Enter description here...
     *
     * @param string $name
     * @return Mage_Core_Block_Abstract
     */
    public function helper($name)
    {
        if ($this->getLayout()) {
            return $this->getLayout()->helper($name);
        }
        return Mage::helper($name);
    }

    /**
     * Retrieve formating date
     *
     * @param   string $date
     * @param   string $format
     * @param   bool $showTime
     * @return  string
     */
    public function formatDate($date=null, $format='short', $showTime=false)
    {
        return $this->helper('core')->formatDate($date, $format, $showTime);
    }

    /**
     * Retrieve formating time
     *
     * @param   string $time
     * @param   string $format
     * @param   bool $showDate
     * @return  string
     */
    public function formatTime($time=null, $format='short', $showDate=false)
    {
        return $this->helper('core')->formatTime($time, $format, $showDate);
    }

    /**
     * Retrieve module name of block
     *
     * @return string
     */
    public function getModuleName()
    {
        $module = $this->getData('module_name');
        if (is_null($module)) {
            $class = get_class($this);
            $module = substr($class, 0, strpos($class, '_Block'));
            $this->setData('module_name', $module);
        }
        return $module;
    }

    /**
     * Translate block sentence
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->getModuleName());
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }

    /**
     * @deprecated after 1.4.0.0-rc1
     * @see self::escapeHtml()
     */
    public function htmlEscape($data, $allowedTags = null)
    {
        return $this->escapeHtml($data, $allowedTags);
    }

    /**
     * Escape html entities
     *
     * @param   mixed $data
     * @param   array $allowedTags
     * @return  string
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        return $this->helper('core')->escapeHtml($data, $allowedTags);
    }

    /**
     * Wrapper for standart strip_tags() function with extra functionality for html entities
     *
     * @param string $data
     * @param string $allowableTags
     * @param bool $allowHtmlEntities
     * @return string
     */
    public function stripTags($data, $allowableTags = null, $allowHtmlEntities = false)
    {
        return $this->helper('core')->stripTags($data, $allowableTags, $allowHtmlEntities);
    }

    /**
     * @deprecated after 1.4.0.0-rc1
     * @see self::escapeUrl()
     */
    public function urlEscape($data)
    {
        return $this->escapeUrl($data);
    }

    /**
     * Escape html entities in url
     *
     * @param string $data
     * @return string
     */
    public function escapeUrl($data)
    {
        return $this->helper('core')->escapeUrl($data);
    }

    /**
     * Escape quotes in java scripts
     *
     * @param mixed $data
     * @param string $quote
     * @return mixed
     */
    public function jsQuoteEscape($data, $quote = '\'')
    {
        return $this->helper('core')->jsQuoteEscape($data, $quote);
    }

    /**
     * Alias for getName method.
     *
     * @return string
     */
    public function getNameInLayout()
    {
        return $this->_nameInLayout;
    }

    /**
     * Get chilren blocks count
     * @return int
     */
    public function countChildren()
    {
        return count($this->_children);
    }

    /**
     * Prepare url for save to cache
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeCacheUrl()
    {
        if (Mage::app()->useCache(self::CACHE_GROUP)) {
            Mage::app()->setUseSessionVar(true);
        }
        return $this;
    }

    /**
     * Replace URLs from cache
     *
     * @param string $html
     * @return string
     */
    protected function _afterCacheUrl($html)
    {
        if (Mage::app()->useCache(self::CACHE_GROUP)) {
            Mage::app()->setUseSessionVar(false);
            Varien_Profiler::start('CACHE_URL');
            $html = Mage::getSingleton('core/url')->sessionUrlVar($html);
            Varien_Profiler::stop('CACHE_URL');
        }
        return $html;
    }

    /**
     * Get cache key informative items
     * Provide string array key to share specific info item with FPC placeholder
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return array(
            $this->getNameInLayout()
        );
    }

    /**
     * Get Key for caching block content
     *
     * @return string
     */
    public function getCacheKey()
    {
        if ($this->hasData('cache_key')) {
            return $this->getData('cache_key');
        }
        /**
         * don't prevent recalculation by saving generated cache key
         * because of ability to render single block instance with different data
         */
        $key = $this->getCacheKeyInfo();
        //ksort($key);  // ignore order
        $key = array_values($key);  // ignore array keys
        $key = implode('|', $key);
        $key = sha1($key);
        return $key;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     */
    public function getCacheTags()
    {
        if (!$this->hasData('cache_tags')) {
            $tags = array();
        } else {
            $tags = $this->getData('cache_tags');
        }
        $tags[] = self::CACHE_GROUP;
        return $tags;
    }

    /**
     * Get block cache life time
     *
     * @return int
     */
    public function getCacheLifetime()
    {
        if (!$this->hasData('cache_lifetime')) {
            return null;
        }
        return $this->getData('cache_lifetime');
    }

    /**
     * Load block html from cache storage
     *
     * @return string | false
     */
    protected function _loadCache()
    {
        if (is_null($this->getCacheLifetime()) || !Mage::app()->useCache(self::CACHE_GROUP)) {
            return false;
        }

        /* START: Changed by Cm_Diehard */
        // If cache load is successful, add cache record tags to diehard tags
        $cacheKey = $this->getCacheKey();
        $data = Mage::app()->loadCache($cacheKey);
        if ($data && Mage::helper('diehard')->getLifetime()) {
            $metadata = Mage::app()->getCacheInstance()->getFrontend()->getMetadatas($cacheKey);
            if ($metadata && ! empty($metadata['tags'])) {
                Mage::helper('diehard')->addTags($metadata['tags']);
            }
        }
        return $data;
        /* END: Changed by Cm_Diehard */
    }

    /**
     * Save block content to cache storage
     *
     * @param string $data
     * @return Mage_Core_Block_Abstract
     */
    protected function _saveCache($data)
    {
        /* START: Changed by Cm_Diehard */
        // Add block cache tags to diehard tags
        $tags = $this->getCacheTags();
        if(Mage::helper('diehard')->getLifetime()) {
            Mage::helper('diehard')->addTags($tags);
        }
        /* END: Changed by Cm_Diehard */

        if (is_null($this->getCacheLifetime()) || !Mage::app()->useCache(self::CACHE_GROUP)) {
            return false;
        }

        Mage::app()->saveCache($data, $this->getCacheKey(), $tags, $this->getCacheLifetime());
        return $this;
    }

    /*
     *  Changes to core methods (above):
     *   - toHtml
     *   - _loadCache
     *   - _saveCache
     *  Additions for Cm_Diehard module (below):
     */

    /**
     * @param string $selector
     * @return Mage_Core_Block_Abstract
     */
    public function setBlockIsDynamic($selector = '')
    {
        // Generic rendering mode
        if (Mage::registry('diehard_lifetime')) {
            if ( ! $selector) {
                $selector = $this->getDiehardSelector();
                $this->setFrameTags('div id="'.substr($selector,1).'"', 'div');
            }
            $helper = Mage::helper('diehard'); /* @var $helper Cm_Diehard_Helper_Data */
            $helper->addDynamicBlock($selector, $this->getNameInLayout());
        }
        // Specific rendering mode
        else {
            $this->setData('diehard_selector', $selector ? $selector : $this->getDiehardSelector());
        }
        return $this->setData('block_is_dynamic', TRUE);
    }

    /**
     * Get or generate an html id for the replacement block
     * @return mixed|string
     */
    public function getDiehardSelector()
    {
        if ( ! $this->hasData('diehard_selector')) {
            return '#dh_'.preg_replace('/[^a-zA-Z0-9]+/', '_', $this->getNameInLayout());
        }
        return $this->getData('diehard_selector');
    }

    /**
     * Convenient method of manipulating cache for the current page via layout updates.
     */
    public function setDiehardCacheLifetime($lifetime = FALSE)
    {
        $helper = Mage::helper('diehard'); /* @var $helper Cm_Diehard_Helper_Data */
        if ($helper->isEnabled() && $this->getRequest()->isGet()) {
            $helper->setLifetime($lifetime);
        }
    }

    /**
     * @param array $args
     */
    public function ignoreBlockIf($args)
    {
        return $this->ignoreBlockIf($args, FALSE);
    }

    /**
     * @param array $args
     * @param bool $ignore
     */
    public function ignoreBlockUnless($args, $ignore = TRUE)
    {
        if ( ! is_array($args)) {
            $args = array($args);
        }
        $helper = Mage::helper('diehard'); /* @var $helper Cm_Diehard_Helper_Data */
        $ignoreBlock = $ignore;
        foreach ($args as $arg) {
            if ( ! strpos($arg, '::')) continue;
            list($namespace, $key) = explode('::', $arg, 2);
            if (Mage::getSingleton($namespace)->getDataUsingMethod($key)) {
                $ignoreBlock = !$ignore;
                break;
            }
        }
        if ($ignoreBlock) {
            $helper->addIgnoredBlock($this);
        } else {
            $helper->removeIgnoredBlock($this);
        }
    }

    /**
     * Workaround to allow easily adding methods to a block without overriding the block.
     * The specified helper is called with the method name being the block's "name" in the layout
     * with non-alphanumeric characters replaced with underscores and the first argument being the
     * block instance. Note that the method is only called if the page is being cached.
     *
     * Layout Example:
     * <reference name="right.links">
     *     <action method="callHelper"><helper>foo</helper></action>
     *     <action method="callHelper"><helper>foo::bar</helper></action>
     * </reference>
     *
     * Effectively:
     * Mage::helper('foo')->right_links($block);
     * Mage::helper('foo')->bar($block);
     *
     * param string $helperName
     */
    public function callHelper()
    {
        if ( ! Mage::registry('diehard_lifetime')) {
            return;
        }

        $args = func_get_args();
        $helperName = array_shift($args);
        array_unshift($args, $this);
        if ( ! $helperName) {
            Mage::throwException('Helper not specified to callHelper.');
        }
        if (strpos($helperName, '::') !== false) {
            list($helperName, $method) = explode('::', $helperName, 2);
        } else {
            $method = $this->getNameInLayout();
        }
        $method = preg_replace('/[^a-zA-Z0-9]+/', '_', $method);
        $helper = Mage::helper($helperName);
        call_user_func_array(array($helper, $method), $args);
    }

    /**
     * Add this block to the list of blocks which are ignored by default
     */
    public function addDefaultIgnored()
    {
        $helper = Mage::helper('diehard'); /* @var $helper Cm_Diehard_Helper_Data */
        $helper->addDefaultIgnoredBlock($this);
    }

  /**
   * Add cache tags from a model as tags for the page.
   * Supports three types:
   *
   *  - data: $block->getData($key)
   *  - singleton: Mage::getSingleton($key)
   *  - registry: Mage::registry($key)
   *
   * @param $type
   * @param $key
   */
  public function addCacheTagsFromModel($type, $key)
    {
        switch($type) {
            case 'data':
                $model = $this->getData($key);
                break;
            case 'singleton':
                $model = Mage::getSingleton($key);
                break;
            case 'registry':
                $model = Mage::registry($key);
                break;
            default:
                Mage::throwException('Unrecognized argument: '.$type);
        }
        if ($model && $model instanceof Mage_Core_Model_Abstract && $model->getId()) {
            if ($tags = $model->getCacheIdTags()) {
                $helper = Mage::helper('diehard'); /* @var $helper Cm_Diehard_Helper_Data */
                $helper->addTags($tags);
            }
        }
    }

}

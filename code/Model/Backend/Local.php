<?php
/**
 * This cache backend uses a local cache instance for full page cache. By default it uses
 * Magento's main cache storage, but a separate cache storage can also be specified.
 *
 * Pros:
 *   - Drop-in and go, no additional requirements
 *   - Offers additional flexibility when determining if cached response should be used
 *   - Cache clearing and invalidation is handled instantly and automatically
 *   - Experimental: can do dynamic replacement without using Ajax
 * Cons:
 *   - Magento is still loaded (but, controller is only dispatched when necessary)
 *
 *
 * To use this backend you must add it to the cache request processors in app/etc/local.xml:
 *
 * <global>
 *     ...
 *     <cache>
 *         ...
 *         <request_processors>
 *             <diehard>Cm_Diehard_Model_Backend_Local</diehard>
 *         </request_processors>
 *         <restrict_nocache>1</restrict_nocache> <!-- Set to 1 to serve cached response in spite of no-cache request header -->
 *     </cache>
 *     <diehard_cache><!-- OPTIONAL -->
 *         <backend>...</backend>
 *         <backend_options>
 *             ...
 *         </backend_options>
 *     </diehard_cache>
 * </cache>
 *
 * @package     Cm_Diehard
 * @author      Colin Mollenhour
 */
class Cm_Diehard_Model_Backend_Local extends Cm_Diehard_Model_Backend_Abstract
{

    const XML_PATH_RESTRICT_NOCACHE               = 'global/cache/restrict_nocache';
    const XML_PATH_DIEHARD_CACHE                  = 'global/diehard_cache';

    protected $_name = 'Local';

    /* Supported methods: */
    protected $_useAjax = TRUE;
    protected $_useEsi  = TRUE;
    protected $_useJs   = TRUE;

    protected static $_useCachedResponse = NULL;
    protected $_cache;

    protected $_defaultBackendOptions = array(
        'file_name_prefix'          => 'diehard',
    );

    /**
     * Clear all cached pages
     *
     * @return void
     */
    public function flush()
    {
        if ($this->_getCacheInstance() == Mage::app()->getCacheInstance()) {
            $this->_getCacheInstance()->cleanType('diehard');
        } else {
            $this->_getCacheInstance()->flush();
        }
    }

    /**
     * Called by 'application_clean_cache' observer
     *
     * @param  $tags
     * @return void
     */
    public function cleanCache($tags)
    {
        // Additional cleaning only necessary if using a separate backend
        if ($this->_getCacheInstance() != Mage::app()->getCacheInstance()) {
            $this->_getCacheInstance()->clean($tags);
        }
    }

    /**
     * Save the response body in the cache before sending it.
     *
     * @param Mage_Core_Controller_Response_Http $response
     * @param  $lifetime
     * @return void
     */
    public function httpResponseSendBefore(Mage_Core_Controller_Response_Http $response, $lifetime)
    {
        // Do not overwrite cached response if response was pulled from cache or cached response
        // was invalidated by an observer of the `diehard_use_cached_response` event
        if ($this->getUseCachedResponse() === NULL)
        {
            $cacheKey = $this->getCacheKey();
            $tags = $this->helper()->getTags();
            $tags[] = Cm_Diehard_Helper_Data::CACHE_TAG;
            $this->_getCacheInstance()->save($response->getBody(), $cacheKey, $tags, $lifetime);
        }

        // Inject dynamic content replacement at end of body
        $body = $response->getBody('default');
        if ($this->useJs()) {
            $params = $this->extractParamsFromBody($body);
            if ($params) {
                // Replace params with rendered blocks
                $dynamic = $this->getDynamicBlockReplacement($params);
                $body = $this->replaceParamsInBody($body, $dynamic);
            }
            $response->setBody($body, 'default');
        }
    }

    /**
     * Flag to avoid caching the response if it was just pulled from the cache.
     * Also, observers of the `diehard_use_cached_response` event may use this 
     * to disallow sending of a cached response for any given request.
     *
     * @param bool $flag
     */
    public function setUseCachedResponse($flag)
    {
        self::$_useCachedResponse = $flag;
    }

    /**
     * @return bool
     */
    public function getUseCachedResponse()
    {
        return self::$_useCachedResponse;
    }

    /**
     * This method is called by Mage_Core_Model_Cache->processRequest()
     *
     * @param  string|bool $content
     * @return bool
     */
    public function extractContent($content)
    {
        if ( ! Mage::app()->useCache('diehard')) {
            return FALSE;
        }
        if ( ! empty($_SERVER['HTTP_CACHE_CONTROL'])
          && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'
          && ! (int) Mage::app()->getConfig()->getNode(self::XML_PATH_RESTRICT_NOCACHE)
        ) {
            return FALSE;
        }
        $cacheKey = $this->getCacheKey();
        if ($this->_getCacheInstance()->getFrontend()->test($cacheKey)) {
            $this->setUseCachedResponse(TRUE);

            // Allow external code to cancel the sending of a cached response
            if (0 /* TODO optional events_enabled feature */) {
                Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_CONFIG);
                Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);
                Mage::dispatchEvent('diehard_use_cached_response', array('backend' => $this));
            }

            if ($this->getUseCachedResponse()) {
                if ($body = $this->_getCacheInstance()->load($cacheKey)) {
                    // Inject dynamic content replacement at end of body
                    $params = $this->extractParamsFromBody($body);
                    if ($params) {
                        // Get list of blocks to render and set ignored blocks cookie if not set
                        $blocksToRender = $params['blocks'];
                        $ignoredBlocks = $this->helper()->getIgnoredBlocks();
                        if ($ignoredBlocks === NULL) {
                            $ignoredBlocks = $params['default_ignored_blocks'];
                            $this->helper()->setIgnoredBlocks($ignoredBlocks);
                        }
                        $params['blocks'] = array_diff($blocksToRender, $ignoredBlocks);

                        // Replace params with rendered blocks
                        $dynamic = $this->getDynamicBlockReplacement($params);
                        $body = $this->replaceParamsInBody($body, $dynamic);
                    }
                    Mage::app()->getResponse()->setHeader('X-Diehard', 'HIT');
                    Mage::register('diehard_cache_hit', TRUE);
                    $counter = new Cm_Diehard_Helper_Counter;
                    $counter->logRequest($params ? $params['full_action_name'] : NULL, TRUE);
                    return $body;
                } else {
                    $this->setUseCachedResponse(NULL);
                }
            }
        }
        return FALSE;
    }

    /**
     * Calls the diehard/load controller without spawning a new request
     *
     * @param array $params
     * @return string
     */
    public function getDynamicBlockReplacement($params)
    {
        // Append dynamic block content to end of page to be replaced by javascript, but not Ajax
        if ($params['blocks'] || ! empty($params['all_blocks']))
        {
            // Init store if it has not been yet (page served from cache)
            if ( ! $this->helper()->isAppInited()) {
                $appParams = Mage::registry('application_params');
                Mage::app()->init($appParams['scope_code'], $appParams['scope_type'], $appParams['options']);
            }
            // Reset parts of app if it has been init'ed (page not served from cache but being saved to cache)
            else {
                // Reset layout
                Mage::unregister('_singleton/core/layout');
                Mage::getSingleton('core/layout');
                // TODO Mage::app()->getLayout() is not reset using the method above!
                // TODO Consider resetting Magento entirely using Mage::reset();
            }

            // Create a subrequest to get JSON response
            $uri = $this->getBaseUrl() . '/_diehard/load/ajax';
            $request = new Mage_Core_Controller_Request_Http($uri);
            $request->setRouteName('diehard');
            $request->setModuleName('_diehard');
            $request->setControllerName('load');
            $request->setActionName('ajax');
            $request->setControllerModule('Cm_Diehard');
            $request->setParam('full_action_name', $params['full_action_name']);
            if ( ! empty($params['all_blocks'])) {
                $request->setParam('all_blocks', 1);
            } else {
                $request->setParam('blocks', $params['blocks']);
            }
            $request->setParam('params', $params['params']);
            $request->setDispatched(true);
            $response = new Mage_Core_Controller_Response_Http;
            require_once Mage::getModuleDir('controllers', 'Cm_Diehard') . '/LoadController.php';
            $controller = new Cm_Diehard_LoadController($request, $response);
            $controller->dispatch('json');

            $replacement = '';
            if ($this->helper()->isDebug()) {
                $replacement .= '<!-- Dynamic blocks rendered: '.(empty($params['all_blocks']) ? implode(',', $params['blocks']) : 'ALL').' -->'."\n";
            }
            $replacement .= "<script type=\"text/javascript\">/* <![CDATA[ */Diehard.replaceBlocks({$response->getBody()});/* ]]> */</script>";
            return $replacement;
        }

        // No dynamic blocks at this time
        else {
            if ($this->helper()->isDebug()) {
                return '<!-- No dynamic blocks -->';
            } else {
                return '';
            }
        }
    }

    /**
     * Returns either the Magento app cache instance or a custom cache instance
     *
     * @return Mage_Core_Model_Cache
     */
    protected function _getCacheInstance()
    {
        if ( ! $this->_cache) {
            if ($cacheConfig =  Mage::app()->getConfig()->getNode(self::XML_PATH_DIEHARD_CACHE)) {
                $this->_defaultBackendOptions['cache_dir'] = Mage::getBaseDir('var').DS.'diehard_cache';
                Mage::app()->getConfig()->createDirIfNotExists($this->_defaultBackendOptions['cache_dir']);
                $options = $cacheConfig->asArray();
                if ( ! isset($options['backend_options'])) {
                    $options['backend_options'] = array();
                }
                $options['backend_options'] = array_merge($this->_defaultBackendOptions, $options['backend_options']);
                $this->_cache = Mage::getModel('core/cache', $options);
            } else {
                $this->_cache = Mage::app()->getCacheInstance();
            }
        }
        return $this->_cache;
    }

}

# Cm_Diehard: Full-Page Cache  #

This module aims to make it easy to serve up cacheable HTML pages without falling back to
fully-dynamic pages as soon as the visitor takes an individualized action like adding a product
to their cart. It has several cache backend models to choose from and supports dynamic block
replacement via Ajax, ESI (edge-side includes) or non-Ajax Javascript. This hole-punching is only performed as-needed
by using a cookie to keep track of which blocks (if any) need to be dynamic. The backends also differ
in the way that cache invalidation is handled by using some interesting techniques; some of which
allow for _real-time_ cache invalidation even with a caching reverse proxy! The rendering technique allows
for users with dynamic blocks to still warm the cache for other users to further increase the cache hit rate.

For a sample implementation of `Cm_Diehard` for OpenMage see [Cm_DiehardSample](https://github.com/colinmollenhour/Cm_DiehardSample).

## Backends

There are currently three backends and three hole-punching (injection) methods. Not all backends support
all injection methods. The backend and supported injection methods are:

- Local Backend
  - Javascript (supports "Early Flush", similar to Facebook's "BigPipe" method)
  - Ajax
  - ESI
- Proxy Backend
  - Ajax
  - ESI
- Revalidating Backend
  - Ajax
  - ESI

Note that all injection methods use a single injection point rather than one for every "hole".

### Local Backend

This backend is like the Enterprise FPC except it currently uses Javascript (but not Ajax) for hole
punching. It does a lightweight cached response by default, and appends the dynamic blocks to the response.
The purpose for this backend is mainly to make testing easier in the absence of a reverse proxy although
it should still have much better performance than no caching at all (600% improvement on a clean install).

This backend may use either the OpenMage app cache instance (default), or a separately configured cache instance
by configuring `global/diehard_cache/backend` and `global/diehard_cache/backend_options`.

#### Pros
- Drop-in and go, no additional requirements
- Offers additional flexibility when determining if cached response should be used
- Cache clearing and invalidation is handled instantly and automatically
- Experimental: can do dynamic replacement without using Ajax
#### Cons
- OpenMage is still loaded (but, controller is only dispatched when necessary)

To enable this backend you must add it as a request processor to `app/etc/local.xml`:

```xml
<config>
    <global>
        <cache>
            ...
            <request_processors>
                <diehard>Cm_Diehard_Model_Backend_Local</diehard>
            </request_processors>
            <restrict_nocache>1</restrict_nocache> <!-- Set to 1 to serve cached response in spite of no-cache request header -->
            <early_flush>1</early_flush> <!-- Flush cached portion of page before rendering dynamic portion -->
        </cache>
        <diehard_cache><!-- OPTIONAL -->
            <backend>...</backend>
            <backend_options>
                ...
            </backend_options>
        </diehard_cache>
    </global>
</config>
```

### Proxy Backend

This backend renders the response generically and uses hole-punching with Ajax or ESI as needed. Cache invalidation
is handled by dumping a list of URLs to invalidate to a file and the actual invalidation of those URLs on the
 reverse proxy is left up to you.

### Revalidating Backend

This backend is like the Proxy backend in that it uses HTTP headers to signal the upstream reverse proxy to
cache the response, but it uses the "must-revalidate" cache control feature of HTTP to make the reverse proxy
revalidate every request. Revalidation requests are handled by a base init of OpenMage and are therefore very
lightweight in the case of a cache hit. This makes it easy to use a caching reverse proxy upstream without
explicitly invalidating large numbers of cached pages on a potentially large numbers of edge servers. Safely use a CDN for
your html and still enjoy real-time invalidation! Ajax and ESI hole-punching are supported.

ETag or Last-Modified headers are used to communicate if the cached content is stale so make sure
your proxy supports revalidation and choose the proper method. Weak ETags are used since byte-range
requests are not supported.

Pros:
  - Off-loads storage of cache to remote server keeping outbound bandwidth for hits at the edge for scalability.
  - Requires no direct invalidation on the remote server since every request is revalidated.
  - You can use CDNs/proxies for your cache frontend and still have instant invalidation with no custom integration - eliminating weird bugs or race conditions.
  - Can be used with the browser's cache for easy testing in a dev environment with no CDN.
Cons:
  - Every request will still hit PHP, but the cache hit will be *much* more efficient than a miss and use negligible bandwidth.

Reverse-proxy servers:
  - Squid (IMS: Yes, INM: NO)
  - Nginx (IMS: Yes, INM: Partial)
  - Apache (IMS: Yes, INM: buggy, possibly fixed in 2.4)
  - Varnish (IMS: Yes, INM: No (experimental-ims branch for 3.x series maybe)

Third-party services that definitely support revalidation:
  - Cloudfront

Third-party services that probably support revalidation (unconfirmed):
  - Akamai
  - Limelight
  - EdgeCast

To use this backend you must add it to the cache request processors in `app/etc/local.xml`:

```xml
<config>
    <global>
        <cache>
            <request_processors>
                <diehard>Cm_Diehard_Model_Backend_Revalidating</diehard>
            </request_processors>
        </cache>
    </global>
</config>
```

## Enabling Cache

By default, no caching is enabled by `Cm_Diehard`. There are three methods of enabling caching but in
all cases it is recommended to implement your caching scheme in a separate module from `Cm_Diehard`.
A falsey value (0, null, false, '') for the cache lifetime disables caching for the current page.
                                              
For a sample implementation of `Cm_Diehard` for OpenMage see [Cm_DiehardSample](https://github.com/colinmollenhour/Cm_DiehardSample).

### config.xml

Set the lifetime in seconds based on the full action name in `config.xml`:

```xml
<config>
    <frontend>
        <diehard>
            <actions>
                <cms_index_index>86400</cms_index_index>
                <cms_page_view>86400</cms_page_view>
            </actions>
        </diehard>
    </frontend>
</config>
```

### Layout updates

Every block inherits a new method named "setDiehardCacheLifetime" which takes the desired lifetime
in seconds. Layout updates will override the values in config.xml.

```xml
<layout>
    <cms_page>
        <reference name="root">
            <action method="setDiehardCacheLifetime"><int>300</int></action>
        </reference>
    </cms_page>
</layout>
```

### Event observers, controllers, blocks, etc..

In any code before the `http_response_send_before` event is dispatched, you can set the lifetime
using the 'diehard' helper. The helper is added to the OpenMage registry with the key 'diehard' so
that you can easily make your code not dependent on Cm_Diehard.

```php
    Mage::registry('diehard') && Mage::helper('diehard')->setLifetime(300);
```

## Public vs Private Domain

You must take care that when caching is enabled for a page that you don't render anything that is
specific to an individual visitor. The classic example is the "My Cart" link in the header which
displays the number of items in the cart and the "Log In"/"Log Out" links. There are many ways to
handle these private or "dynamic" blocks in cached pages. In general you must render generically
for the cached responses and then regularly for the hole-punching responses. Here are some methods:

### Placeholders only

With this method the block will render only an empty div used as a placeholder for cached responses
and then regularly for dynamic block injection. The advantage of this method is that no templates
need to be modified or added and no block class overrides are needed, just a simple layout update.

```xml
<!-- LAYOUT -->
    <block type="core/template" name="greeting" template="mymodule/greeting.phtml">
        <action method="setBlockIsDynamic"></action>
        <action method="setSuppressOutput"><param>1</param></action>
    </block>
```

```php
<!-- TEMPLATE mymodule/greeting.phtml -->
<?php if ( ! Mage::helper('customer')->isLoggedIn() ): ?>
<p><?php echo $this->__('Welcome!') ?></p>
<?php else: ?>
<p><?php echo $this->__('Welcome back, %s!', Mage::helper('customer')->getCustomerName()) ?>
<?php endif; ?>
```


### Separate "cache-friendly" templates

This method allows you to have a different template for cache-friendly output without having to modify
any pre-existing templates or add logic to your template files.

```xml
<!-- LAYOUT -->
    <block type="core/template" name="greeting" template="mymodule/greeting.phtml">
        <action method="setBlockIsDynamic"></action>
        <action method="setCacheFriendlyTemplate"><param>mymodule/cache-friendly/greeting.phtml</param></action>
    </block>
```

```php
<!-- TEMPLATE mymodule/greeting.phtml -->
<?php if ( ! Mage::helper('customer')->isLoggedIn() ): ?>
<p><?php echo $this->__('Welcome!') ?></p>
<?php else: ?>
<p><?php echo $this->__('Welcome back, %s!', Mage::helper('customer')->getCustomerName()) ?>
<?php endif; ?>

<!-- TEMPLATE mymodule/cache-friendly/greeting.phtml -->
<p><?php echo $this->__('Welcome!') ?></p>
```

### Add logic using a helper method

The Mage_Core_Block_Abstract class has another helpful method added to it which allows you to pass the block
instance to a helper method so that you can use logic on the block instance without overriding the block or adding
expensive event observers. Note that the method will only be called if the page is being cached.

```xml
<!-- LAYOUT -->
    <reference name="checkout_cart_link">
        <action method="callHelper"><helper>my_diehard</helper></action>
    </reference>
```

```php
<!-- My_Diehard_Helper_Data -->
    /**
     * Always render cart link as "My Cart" when caching is active.
     *
     * @param Mage_Checkout_Block_Links $block
     */
    public function checkout_cart_link(Mage_Checkout_Block_Links $block)
    {
        $parentBlock = $block->getParentBlock(); /* @var $parentBlock Mage_Page_Block_Template_Links */
        $text = $block->__('My Cart');
        $parentBlock->removeLinkByUrl($block->getUrl('checkout/cart'));
        $parentBlock->addLink($text, 'checkout/cart', $text, true, array(), 50, null, 'class="top-link-cart"');
    }
```

### Add logic to templates or block methods

This method may be preferred if only a small portion of a template needs to be different for cached
responses and you don't want to have separate template files or use the empty placeholders.

```xml
<!-- LAYOUT -->
    <block type="core/template" name="greeting" template="mymodule/greeting.phtml">
        <action method="setBlockIsDynamic"></action>
    </block>
```

```php
<!-- TEMPLATE mymodule/greeting.phtml -->
    <?php if ( Mage::registry('diehard_lifetime') || ! Mage::helper('customer')->isLoggedIn() ): ?>
    <p><?php echo $this->__('Welcome!') ?></p>
    <?php else: ?>
    <p><?php echo $this->__('Welcome back, %s!', Mage::helper('customer')->getCustomerName()) ?>
    <?php endif; ?>
```

## Modularity and Backwards-Compatibility

In order to keep your packages/themes operable without Cm_Diehard yet still allow for maximum extensibility Diehard injects some
special layout update handles into the layout update process. The **`DIEHARD_CACHED_default`** handle will be added after the
`default` handle and the **`DIEHARD_CACHED_{full_action_name}`** handle will be added after the `{full_action_name}` handle **if and
only if** caching is enabled for the page being rendered before the controller calls the `loadLayout` method. This provides the
following benefits:

 - Specify layout updates that will only be applied when the request is one that will be cached.
 - Separate your caching-specific layout updates from the rest of your layout.
 - Call block methods added by Cm_Diehard without making the theme dependent on Cm_Diehard being installed.

Here is an example of a theme's `local.xml` file that will work with or without Cm_Diehard present:

```xml
<!-- LAYOUT -->
    <catalog_product_view>
        <reference name="content">
            <block type="my/custom_block" name="my_block" />
        </reference>
    </catalog_product_view>
    <DIEHARD_CACHED_catalog_product_view>
        <reference name="my_block">
            <action method="setBlockIsDynamic"></action>
            <action method="addDefaultIgnored"></action>
        </reference>
    </DIEHARD_CACHED_catalog_product_view>
```

## Rendering Dynamic Blocks

For the dynamic blocks to be rendered they need to be made accessible to the layout. By default
there are no blocks in the layout handles that are used to render the dynamic blocks, they must be
added as needed. The layout handles that should be used are: `DIEHARD_DYNAMIC_default` and `DIEHARD_DYNAMIC_{full_action_name}`.
This allows you to keep the layout for the rendering of dynamic blocks lightweight, but as with any
other layout handle, these can inherit from others so you generally have two methods of adding blocks
to the dynamic renderer layout:

### Inheritance

```xml
<!--LAYOUT -->
    <DIEHARD_DYNAMIC_default>
        <update handle="default"/>
    </DIEHARD_DYNAMIC_default>
```

### À la carte

```xml
<!-- LAYOUT -->
    <DIEHARD_DYNAMIC_default>
        <block type="core/template" name="greeting" template="mymodule/greeting.phtml"/>
    </DIEHARD_DYNAMIC_default>
```

When using this method you must be sure that the block names match the corresponding blocks in the
layout used for the cached response. Blocks can be added directly to the root of the layout handle,
they do not need to be children of another block.

## Adding and Removing "Ignored" Dynamic Blocks

In some cases the blocks indicated as being dynamic may not always actually be dynamic. If the
cached version of the page will only need to be updated under certain circumstances you can add
the block to a list of "ignored" blocks and remove it from the list when it needs to become dynamic
again. When using Ajax for dynamic block injection, if all dynamic blocks are on the ignored blocks
list then the Ajax request will be skipped entirely.

### Ignore blocks via layout updates

The methods available to blocks are `ignoreBlockUnless()`, which causes the block to be ignored **unless** the specified
session data is present, and `ignoreBlockIf()` which causes the block to be ignored **if** the session data is present.

```xml
<!-- LAYOUT -->
    <reference name="greeting">
        <action method="ignoreBlockUnless">
            <variables>
                <customer>customer/session::customer_id</customer>
            </variables>
        </action>
    </reference>
```

### Ignore blocks via controllers or block methods

For greater flexibility you may need to add some logic to the controller, a block prepareLayout method
or an event observer.

```php
    /* CONTROLLER or BLOCK prepareLayout() method or EVENT OBSERVER*/
    if (Mage::registry('diehard')) { // Prevent errors in absence of Cm_Diehard module
        if ($this->getSession()->getSomeVariable()) {
            Mage::registry('diehard')->removeIgnoredBlock($this);
        } else {
            Mage::registry('diehard')->addIgnoredBlock($this);
        }
    }
```

## Default Ignored Blocks

In order to allow for proper handling of the case where a user hits a cached page on his first visit
and there would be no way to know which blocks can be ignored, a list of "default" ignored blocks is
generated for each page to be used when the user has not yet been cookied. A block can be added to
the list of default ignored blocks at any time during page rendering using either the layout or the
helper method.

### Layout

The easiest to maintain method may be in the layout.

```xml
<!-- LAYOUT -->
    <reference name="greeting">
        <action method="addDefaultIgnored" />
    </reference>
```

### Helper

```php
    /* Event Observer, constructor or other */
    Mage::helper('diehard')->addDefaultIgnoredBlock('greeting');
```

In both examples the "greeting" block will be ignored by default for all users until it is explicitly removed
from the ignored blocks list.

## Hit Rate Monitor

Redis can be used for monitoring the hit-rate of the cache on the Revalidate backend and the Local backend.
To enable this feature you must have [Credis_Client](https://packagist.org/packages/colinmollenhour/credis) installed.

Then set up your configuration in `app/etc/local.xml`:

```xml
<!-- CONFIG -->
  <global>
    ...
    <diehard>
      <counter>
        <enabled>1</enabled>
        <server>tcp://127.0.0.1:6379/diehard</server>
        <db>0</db>
        <full_action_name>1</full_action_name>
      </counter>
    </diehard>
  </global>
```

The above configuration example enables hit and miss counters to be updated on every request to the
specified Redis server. Monitoring these stats is currently left as an exercise for you, but the keys can easily be queried:

```
redis-cli get diehard:hit
redis-cli get diehard:miss
redis-cli get diehard:catalog_product_view:hit
etc...
```

Check `diehard_counter.log` for errors if stats are not being logged.

## Offloader header

As the status of the connection needs to be determined before the full config is initialized the "Web > Secure > Offloader header"
configuration needs to be added to `app/etc/local.xml` to have an effect on the request. Note, the default behavior already
considers the following criteria when detecting a secure connection:

- `$_SERVER['HTTPS'] === 'on'` as set by Apache
- `$_SERVER['SERVER_PORT'] === 443` as set by Apache
- `X-Forwarded-Proto: https` header is present as set by many common proxies

If none of those are true, but there is another header that you use to indicate a secure connection add this to your `app/etc/local.xml`
even if this is already set via the Admin UI:

```xml
<config>
    <default>
        <web>
            <secure>
                <offloader_header>SSL_OFFLOADED</offloader_header>
            </secure>
        </web>
    </default>
</config>
```

## License

This module is distributed under the GPLv3 license. To receive a copy
under a different license please contact the author.

Cm_Diehard - Full-page caching library for OpenMage.
Copyright (C) 2012  Colin Mollenhour (http://colin.mollenhour.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/

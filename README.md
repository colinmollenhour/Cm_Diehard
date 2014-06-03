# Cm_Diehard: Full-Page Cache  #

This module aims to make it easy to serve up cacheable HTML pages without falling back to
fully-dynamic pages as soon as the visitor takes an individualized action like adding a product
to their cart. It has several cache backend models to choose from and supports dynamic block
replacement via Ajax, ESI (edge-side includes) or non-Ajax Javascript. This hole-punching is only performed as-needed
by using a cookie to keep track of which blocks (if any) need to be dynamic. The backends also differ
in the way that cache invalidation is handled by using some interesting techniques; some of which
allow for _real-time_ cache invalidation even with a caching reverse proxy! The rendering technique allows
for users with dynamic blocks to still warm the cache for other users to further increase the cache hit rate.

## Backends

There are currently three backends and three hole-punching (injection) methods. Not all backends support
all injection methods. The backend and supported injection methods are:

- Local Backend
 - Javascript (served inline with the response)
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
it should still have much better performance than no caching at all.

This backend may use either the Magento app cache instance (default), or a separately configured cache instance
by configuring `global/diehard_cache/backend` and `global/diehard_cache/backend_options`.

### Proxy Backend

This backend renders the response generically and uses hole-punching with Ajax or ESI as needed. Cache invalidation
is handled by dumping a list of URLs to invalidate to a file and the actual invalidation of those URLs on the
 reverse proxy is left up to you.

### Revalidating Backend

This backend is like the Proxy backend in that it uses HTTP headers to signal the upstream reverse proxy to
cache the response, but it uses the "must-revalidate" cache control feature of HTTP to make the reverse proxy
revalidate every request. Revalidation requests are handled by a base init of Magento and are therefore very
lightweight in the case of a cache hit. This makes it easy to use a caching reverse proxy upstream without
explicitly invalidating large numbers of cached pages on a potentially large numbers of edge servers. Safely use a CDN for
your html and still enjoy real-time invalidation! Ajax and ESI hole-punching are supported.

## Enabling Cache

By default no caching is enabled by Cm_Diehard. There are three methods of enabling caching but in
all cases it is recommended to implement your caching scheme in a separate module from Cm_Diehard.
A falsey value (0, null, false, '') for the cache lifetime disables caching for the current page.

### config.xml

Set the lifetime in seconds based on the full action name in config.xml:

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
    <cms_page_view>
        <reference name="root">
            <action method="setDiehardCacheLifetime"><int>300</int></action>
        </reference>
    </cms_page_view>
</layout>
```

### Event observers, controllers, blocks, etc..

In any code before the `http_response_send_before` event is dispatched, you can set the lifetime
using the 'diehard' helper. The helper is added to the Magento registry with the key 'diehard' so
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
<?php if( ! Mage::helper('customer')->isLoggedIn() ): ?>
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
    </reference>
```

```php
<!-- TEMPLATE mymodule/greeting.phtml -->
<?php if( ! Mage::helper('customer')->isLoggedIn() ): ?>
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
    <?php if( Mage::registry('diehard_lifetime') || ! Mage::helper('customer')->isLoggedIn() ): ?>
    <p><?php echo $this->__('Welcome!') ?></p>
    <?php else: ?>
    <p><?php echo $this->__('Welcome back, %s!', Mage::helper('customer')->getCustomerName()) ?>
    <?php endif; ?>
```

## Rendering Dynamic Blocks

For the dynamic blocks to be rendered they need to be made accessible to the layout. By default
there are no blocks in the layout handles that are used to render the dynamic blocks, they must be
added as needed. The layout handles that should be used are: DIEHARD_default and DIEHARD_{full_action_name}.
This allows you to keep the layout for the rendering of dynamic blocks lightweight, but as with any
other layout handle, these can inherit from others so you generally have two methods of adding blocks
to the dynamic renderer layout:

### Inheritance

```xml
<!--LAYOUT -->
    <DIEHARD_default>
        <update handle="default"/>
    </DIEHARD>
```

### À la carte

```xml
<!-- LAYOUT -->
    <DIEHARD_default>
        <block type="core/template" name="greeting" template="mymodule/greeting.phtml"/>
    </DIEHARD_default>
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
To enable this feature you must have Credis_Client present in your lib directory:

    git clone git://github.com/colinmollenhour/credis.git lib/Credis

Then setup your configuration in app/etc/local.xml:

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
    ...
  </global>
```

The above configuration example enables hit and miss counters to be updated on every request to the
specified Redis server. Use the included munin script to monitor these values with munin. (TODO)

## License

This module is distributed under the GPLv3 license. To receive a copy
under a different license please contact the author.

Cm_Diehard - Full-page caching library for Magento.
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

# Cm_Diehard: Full-Page Cache with Ajax block loading. #

This module aims to make it easy to serve up cacheable HTML pages without falling back to
fully-dynamic pages as soon as the visitor takes an individualized action like adding a product
to their cart. It has several cache backend models to choose from which have different features like
dynamic block replacement via Ajax or non-Ajax javascript. This hole-punching is only performed as-needed
by using a cookie to keep track of which blocks (if any) need to be dynamic. The backends also differ
in the way that cache invalidation is handled by using some interesting techniques; some of which
allow for _real-time_ cache invalidation even with a caching reverse proxy! The rendering technique allows
for users with dynamic blocks to still warm the cache for other users to further increase the cache hit rate.

## Backends

### Magento Backend

This backend is like the Enterprise FPC except it does server-side hole punching. It does a lightweight
cached response by default, and appends some javascript to inject the dynamic blocks when needed.

### Proxy Backend

This backend renders the response generically and uses hole-punching with Ajax as needed. Cache invalidation
is handled by dumping a list of URLs to invalidate to a file and the actual invalidation of those URLs on the
 reverse proxy is left up to you.

### Revalidating Backend

This backend is like the Proxy backend in that it uses HTTP headers to signal the upstream reverse proxy to
cache the response, but it uses the "must-revalidate" cache control feature of HTTP to make the reverse proxy
revalidate every request. Revalidation requests are handled by a base init of Magento and are therefore very
lightweight in the case of a cache hit. This makes it easy to use a caching reverse proxy upstream without
explicitly invalidating large numbers of cached pages on large numbers of edge servers. Safely use a CDN for
your html and still enjoy real-time invalidation!

## Example Block Injection

    LAYOUT:
    <reference name="top.links">
        <action method="setBlockIsDynamic"></action>
    </reference>
    HTML:
    <?php if( Mage::registry('diehard_lifetime') || ! Mage::helper('customer')->isLoggedIn() ): ?>
    <p><?php echo $this->__('Welcome!') ?></p>
    <?php else: ?>
    <p><?php echo $this->__('Welcome back, %s!', Mage::helper('customer')->getCustomerName()) ?>
    <?php endif; ?>

In the above example, the page will load with "Welcome!" and then that will be
replaced shortly after by "Welcome back, Colin Mollenhour!"

In some cases the cached version of the page will only need to be updated under certain circumstances. In this case
you can add the block to a list of "ignored" blocks and remove it from the list when it needs to become dynamic again.

    CONTROLLER or BLOCK prepareLayout:
    if(Mage::registry('diehard'))
        if($this->getSession()->getSomeVariable()) {
            Mage::registry('diehard')->addIgnoredBlock($this);
        } else {
            Mage::registry('diehard')->removeIgnoredBlock($this);
        }
    }

# Aoe_Static: Full-Page Cache with Ajax block loading. #

This module makes it easy to serve up cacheable HTML pages and inject dynamic content after the DOM
is loaded via a single Ajax request.

Example for use inside a template:

    <div id="aoe-foo" class="placeholder" rel="<?php $this->getNameInLayout() ?>">
    <?php if(Mage::registry('aoestatic') && Mage::registry('aoestatic')->isForCache()): ?>
    CACHED
    <?php else: ?>
    <?php echo date('r') ?>
    <?php endif; ?>
    </div>

In the above example, the page will load with CACHED and then CACHED will be replaced
shortly after by the date.

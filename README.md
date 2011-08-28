# Aoe_Static: Full-Page Cache with Ajax block loading. #

This module makes it easy to serve up cacheable HTML pages and inject dynamic content after the DOM
is loaded via a single Ajax request.

Example:

    LAYOUT:
    <reference name="top.links">
        <action method="setBlockIsDynamic"></action>
    </reference>
    HTML:
    <?php if(Mage::registry('aoestatic') && Mage::registry('aoestatic')->getLifetime()): ?>
    CACHED
    <?php else: ?>
    <?php echo date('r') ?>
    <?php endif; ?>

In the above example, the page will load with CACHED and then CACHED will be replaced
shortly after by the current date.

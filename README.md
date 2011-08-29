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
    Today
    <?php else: ?>
    <?php echo date('r') ?>
    <?php endif; ?>

In the above example, the page will load with "Today" and then "Today" will be replaced
shortly after by the current date.

In some cases the cached version of the page will only need to be updated under certain circumstances. In this case
you can add the block to a list of "ignored" blocks and remove it from the list when it needs to become dynamic again.

    CONTROLLER or BLOCK prepareLayout:
    if(Mage::registry('aoestatic'))
        if($this->getSession()->getSomeVariable()) {
            Mage::registry('aoestatic')->addIgnoredBlock($this);
        } else {
            Mage::registry('aoestatic')->removeIgnoredBlock($this);
        }
    }

# Cm_Diehard: Full-Page Cache with Ajax block loading. #

This module makes it easy to serve up cacheable HTML pages and inject dynamic content after the DOM
is loaded via a single Ajax request.

Example:

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

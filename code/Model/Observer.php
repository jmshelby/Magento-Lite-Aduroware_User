<?php
/**
 * User module observer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Observer
{

    /**
     * Before load layout event handler
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeLoadLayout($observer)
    {
        $loggedIn = Mage::getSingleton('user/session')->isLoggedIn();

        $observer->getEvent()->getLayout()->getUpdate()
           ->addHandle('user_logged_' . ($loggedIn ? 'in' : 'out'));
    }

}

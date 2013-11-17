<?php
/**
 * Dashboard User Info
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Aduroware_User_Block_Account_Dashboard_Info extends Mage_Core_Block_Template
{
    public function getUser()
    {
        return Mage::getSingleton('user/session')->getUser();
    }

    public function getChangePasswordUrl()
    {
        return Mage::getUrl('*/account/edit/changepass/1');
    }

}

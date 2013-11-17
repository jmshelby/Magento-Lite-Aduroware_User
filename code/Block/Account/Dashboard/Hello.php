<?php
class Aduroware_User_Block_Account_Dashboard_Hello extends Mage_Core_Block_Template
{

    public function getUserName()
    {
        return Mage::getSingleton('user/session')->getUser()->getName();
    }

}

<?php
/**
 * User dashboard block
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Block_Account_Dashboard extends Mage_Core_Block_Template
{
    protected $_subscription = null;

    public function getUser()
    {
        return Mage::getSingleton('user/session')->getUser();
    }

    public function getAccountUrl()
    {
        return Mage::getUrl('user/account/edit', array('_secure'=>true));
    }

    public function getAddressesUrl()
    {
        return Mage::getUrl('user/address/index', array('_secure'=>true));
    }

    public function getAddressEditUrl($address)
    {
        return Mage::getUrl('user/address/edit', array('_secure'=>true, 'id'=>$address->getId()));
    }

    public function getOrdersUrl()
    {
        return Mage::getUrl('user/order/index', array('_secure'=>true));
    }

    public function getReviewsUrl()
    {
        return Mage::getUrl('review/user/index', array('_secure'=>true));
    }

    public function getWishlistUrl()
    {
        return Mage::getUrl('user/wishlist/index', array('_secure'=>true));
    }

    public function getTagsUrl()
    {

    }

    public function getPrimaryAddresses()
    {
        $addresses = $this->getUser()->getPrimaryAddresses();
        if (empty($addresses)) {
            return false;
        }
        return $addresses;
    }

    /**
     * Get back url in account dashboard
     *
     * This method is copypasted in:
     * Mage_Wishlist_Block_User_Wishlist  - because of strange inheritance
     * Mage_User_Block_Address_Book - because of secure url
     *
     * @return string
     */
    public function getBackUrl()
    {
        // the RefererUrl must be set in appropriate controller
        if ($this->getRefererUrl()) {
            return $this->getRefererUrl();
        }
        return $this->getUrl('user/account/');
    }
}

<?php
/**
 * User sharing config model
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Config_Share extends Mage_Core_Model_Config_Data
{
    /**
     * Xml config path to users sharing scope value
     *
     */
    const XML_PATH_USER_ACCOUNT_SHARE = 'user/account_share/scope';
    
    /**
     * Possible user sharing scopes
     *
     */
    const SHARE_GLOBAL  = 0;
    const SHARE_WEBSITE = 1;

    /**
     * Check whether current users sharing scope is global
     *
     * @return bool
     */
    public function isGlobalScope()
    {
        return !$this->isWebsiteScope();
    }

    /**
     * Check whether current users sharing scope is website
     *
     * @return bool
     */
    public function isWebsiteScope()
    {
        return Mage::getStoreConfig(self::XML_PATH_USER_ACCOUNT_SHARE) == self::SHARE_WEBSITE;
    }

    /**
     * Get possible sharing configuration options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            self::SHARE_GLOBAL  => Mage::helper('user')->__('Global'),
            self::SHARE_WEBSITE => Mage::helper('user')->__('Per Website'),
        );
    }

    /**
     * Check for email dublicates before saving users sharing options
     *
     * @return Aduroware_User_Model_Config_Share
     * @throws Mage_Core_Exception
     */
    public function _beforeSave()
    {
        $value = $this->getValue();
        if ($value == self::SHARE_GLOBAL) {
            if (Mage::getResourceSingleton('user/user')->findEmailDuplicates()) {
                Mage::throwException(
                    Mage::helper('user')->__('Cannot share user accounts globally because some user accounts with the same emails exist on multiple websites and cannot be merged.')
                );
            }
        }
        return $this;
    }
}

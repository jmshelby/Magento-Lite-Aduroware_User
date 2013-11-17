<?php
/**
 * User name helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Helper_Name extends Mage_Core_Helper_Abstract
{

    protected $_config          = array();

    public function getAttributeValidationClass($attributeCode)
    {
        $userAttribute = Mage::getSingleton('eav/config')->getAttribute('user', $attributeCode);
        $class = $userAttribute && $userAttribute->getIsVisible()
            ? $userAttribute->getFrontend()->getClass() : '';
        $class = implode(' ', array_unique(array_filter(explode(' ', $class))));
        return $class;
    }

    public function getConfig($key, $store = null)
    {
        $websiteId = Mage::app()->getStore($store)->getWebsiteId();

        if (!isset($this->_config[$websiteId])) {
            $this->_config[$websiteId] = Mage::getStoreConfig('user/name', $store);
        }
        return isset($this->_config[$websiteId][$key]) ? (string)$this->_config[$websiteId][$key] : null;
    }

    public function canShowConfig($key)
    {
        return (bool)$this->getConfig($key);
    }

}

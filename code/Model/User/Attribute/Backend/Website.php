<?php
/**
 * Website attribute backend
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_User_Attribute_Backend_Website extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{
    public function beforeSave($object)
    {
        if ($object->getId()) {
            return $this;
        }
        if (!$object->hasData('website_id')) {
            $object->setData('website_id', Mage::app()->getStore()->getWebsiteId());
        }
        return $this;
    }
}

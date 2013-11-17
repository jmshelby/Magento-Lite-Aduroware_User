<?php
/**
 * Boolean user attribute backend model
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Attribute_Backend_Data_Boolean extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract
{
    /**
     * Prepare data before attribute save
     *
     * @param Aduroware_User_Model_User $user
     * @return Aduroware_User_Model_Attribute_Backend_Data_Boolean
     */
    public function beforeSave($user)
    {
        $attributeName = $this->getAttribute()->getName();
        $inputValue = $user->getData($attributeName);
        $sanitizedValue = (!empty($inputValue)) ? '1' : '0';
        $user->setData($attributeName, $sanitizedValue);
        return $this;
    }
}

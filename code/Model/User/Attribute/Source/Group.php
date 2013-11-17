<?php
/**
 * User group attribute source
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_User_Attribute_Source_Group extends Mage_Eav_Model_Entity_Attribute_Source_Table
{
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('user/group_collection')
                ->setRealGroupsFilter()
                ->load()
                ->toOptionArray()
            ;
        }
        return $this->_options;
    }
}

<?php
/**
 * User attribute resource model
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Resource_Attribute extends Mage_Eav_Model_Resource_Attribute
{

    /**
     * Get Form attribute table
     *
     * Get table, where dependency between form name and attribute ids is stored
     *
     * @return string|null
     */
    protected function _getFormAttributeTable()
    {
        return $this->getTable('user/form_attribute');
    }

    protected function _getEavWebsiteTable()
	{
        return $this->getTable('user/eav_attribute_website');
	}

}

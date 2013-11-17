<?php
/**
 * User Form Attribute Resource Model
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Resource_Form_Attribute extends Mage_Eav_Model_Resource_Form_Attribute
{
    /**
     * Initialize connection and define main table
     *
     */
    protected function _construct()
    {
        $this->_init('user/form_attribute', 'attribute_id');
    }
}

<?php
/**
 * User Form Attribute Resource Collection
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Resource_Form_Attribute_Collection extends Mage_Eav_Model_Resource_Form_Attribute_Collection
{

    /**
     * Current module pathname
     *
     * @var string
     */
    protected $_moduleName = 'user';

    /**
     * Current EAV entity type code
     *
     * @var string
     */
    protected $_entityTypeCode = 'user';

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('eav/attribute', 'user/form_attribute');
    }

    protected function _getEavWebsiteTable()
    {
        return $this->getTable('user/eav_attribute_website');
    }

}

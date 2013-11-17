<?php
/**
 * User attribute model
 *
 * @category   Mage
 * @package    Aduroware_User
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Attribute extends Mage_Eav_Model_Attribute
{
    /**
     * Name of the module
     */
    const MODULE_NAME = 'Aduroware_User';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'user_entity_attribute';

    /**
     * Prefix of model events object
     *
     * @var string
     */
    protected $_eventObject = 'attribute';

    /**
     * Init resource model
     */
    protected function _construct()
    {
        $this->_init('user/attribute');
    }
}

<?php
/**
 * User group collection
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Resource_Group_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('user/group');
    }

    /**
     * Set tax group filter
     *
     * @param mixed $classId
     * @return Aduroware_User_Model_Resource_Group_Collection
     */
    public function setTaxGroupFilter($classId)
    {
        $this->getSelect()->joinLeft(
            array('tax_class_group' => $this->getTable('tax/tax_class_group')),
            'tax_class_group.class_group_id = main_table.user_group_id'
        );
        $this->addFieldToFilter('tax_class_group.class_parent_id', $classId);
        return $this;
    }

    /**
     * Set ignore ID filter
     *
     * @param array $indexes
     * @return Aduroware_User_Model_Resource_Group_Collection
     */
    public function setIgnoreIdFilter($indexes)
    {
        if (count($indexes)) {
            $this->addFieldToFilter('main_table.user_group_id', array('nin' => $indexes));
        }
        return $this;
    }

    /**
     * Set real groups filter
     *
     * @return Aduroware_User_Model_Resource_Group_Collection
     */
    public function setRealGroupsFilter()
    {
        return $this->addFieldToFilter('user_group_id', array('gt' => 0));
    }

    /**
     * Add tax class
     *
     * @return Aduroware_User_Model_Resource_Group_Collection
     */
    public function addTaxClass()
    {
        $this->getSelect()->joinLeft(
            array('tax_class_table' => $this->getTable('tax/tax_class')),
            "main_table.tax_class_id = tax_class_table.class_id");
        return $this;
    }

    /**
     * Retreive option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return parent::_toOptionArray('user_group_id', 'user_group_code');
    }

    /**
     * Retreive option hash
     *
     * @return array
     */
    public function toOptionHash()
    {
        return parent::_toOptionHash('user_group_id', 'user_group_code');
    }
}

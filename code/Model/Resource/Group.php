<?php
/**
 * User group resource model
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Resource_Group extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('user/user_group', 'user_group_id');
    }

    /**
     * Initialize unique fields
     *
     * @return Aduroware_User_Model_Resource_Group
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = array(
            array(
                'field' => 'user_group_code',
                'title' => Mage::helper('user')->__('User Group')
            ));

        return $this;
    }

    /**
     * Check if group uses as default
     *
     * @param  Mage_Core_Model_Abstract $group
     * @throws Mage_Core_Exception
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeDelete(Mage_Core_Model_Abstract $group)
    {
        if ($group->usesAsDefault()) {
            Mage::throwException(Mage::helper('user')->__('The group "%s" cannot be deleted', $group->getCode()));
        }
        return parent::_beforeDelete($group);
    }

    /**
     * Method set default group id to the users collection
     *
     * @param Mage_Core_Model_Abstract $group
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterDelete(Mage_Core_Model_Abstract $group)
    {
        $userCollection = Mage::getResourceModel('user/user_collection')
            ->addAttributeToFilter('group_id', $group->getId())
            ->load();
        foreach ($userCollection as $user) {
            $defaultGroupId = Mage::helper('user')->getDefaultUserGroupId($user->getStoreId());
            $user->setGroupId($defaultGroupId);
            $user->save();
        }
        return parent::_afterDelete($group);
    }
}

<?php
/**
 * User group model
 *
 * @method Aduroware_User_Model_Resource_Group _getResource()
 * @method Aduroware_User_Model_Resource_Group getResource()
 * @method string getUserGroupCode()
 * @method Aduroware_User_Model_Group setUserGroupCode(string $value)
 * @method Aduroware_User_Model_Group setTaxClassId(int $value)
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Group extends Mage_Core_Model_Abstract
{
    /**
     * Xml config path for create account default group
     */
    const XML_PATH_DEFAULT_ID       = 'user/create_account/default_group';

    const NOT_LOGGED_IN_ID          = 0;
    const CUST_GROUP_ALL            = 32000;

    const ENTITY                    = 'user_group';

    const GROUP_CODE_MAX_LENGTH     = 32;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'user_group';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'object';

    protected static $_taxClassIds = array();

    protected function _construct()
    {
        $this->_init('user/group');
    }

    /**
     * Alias for setUserGroupCode
     *
     * @param string $value
     */
    public function setCode($value)
    {
        return $this->setUserGroupCode($value);
    }

    /**
     * Alias for getUserGroupCode
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getUserGroupCode();
    }

    public function getTaxClassId($groupId = null)
    {
        if (!is_null($groupId)) {
            if (empty(self::$_taxClassIds[$groupId])) {
                $this->load($groupId);
                self::$_taxClassIds[$groupId] = $this->getData('tax_class_id');
            }
            $this->setData('tax_class_id', self::$_taxClassIds[$groupId]);
        }
        return $this->getData('tax_class_id');
    }


    public function usesAsDefault()
    {
        $data = Mage::getConfig()->getStoresConfigByPath(self::XML_PATH_DEFAULT_ID);
        if (in_array($this->getId(), $data)) {
            return true;
        }
        return false;
    }

    /**
     * Processing data save after transaction commit
     *
     * @return Aduroware_User_Model_Group
     */
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();
        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        return $this;
    }

    /**
     * Prepare data before save
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $this->_prepareData();
        return parent::_beforeSave();
    }

    /**
     * Prepare user group data
     *
     * @return Aduroware_User_Model_Group
     */
    protected function _prepareData()
    {
        $this->setCode(
            substr($this->getCode(), 0, self::GROUP_CODE_MAX_LENGTH)
        );
        return $this;
    }

}

<?php
/**
 * User entity resource model
 *
 * @category    Mage
 * @package     Aduroware_User
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Aduroware_User_Model_Resource_User extends Mage_Eav_Model_Entity_Abstract
{
    /**
     * Resource initialization
     */
    public function __construct()
    {
        $this->setType('user');
        $this->setConnection('user_read', 'user_write');
    }

    /**
     * Retrieve user entity default attributes
     *
     * @return array
     */
    protected function _getDefaultAttributes()
    {
        return array(
            'entity_type_id',
            'attribute_set_id',
            'created_at',
            'updated_at',
            'increment_id',
            'store_id',
            'website_id'
        );
    }

    /**
     * Check user scope, email and confirmation key before saving
     *
     * @param Aduroware_User_Model_User $user
     * @throws Aduroware_User_Exception
     * @return Aduroware_User_Model_Resource_User
     */
    protected function _beforeSave(Varien_Object $user)
    {
        parent::_beforeSave($user);

        if (!$user->getEmail()) {
            throw Mage::exception('Aduroware_User', Mage::helper('user')->__('User email is required'));
        }

        $adapter = $this->_getWriteAdapter();
        $bind    = array('email' => $user->getEmail());

        $select = $adapter->select()
            ->from($this->getEntityTable(), array($this->getEntityIdField()))
            ->where('email = :email');
        if ($user->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$user->getWebsiteId();
            $select->where('website_id = :website_id');
        }
        if ($user->getId()) {
            $bind['entity_id'] = (int)$user->getId();
            $select->where('entity_id != :entity_id');
        }

        $result = $adapter->fetchOne($select, $bind);
        if ($result) {
            throw Mage::exception(
                'Aduroware_User', Mage::helper('user')->__('This user email already exists'),
                Aduroware_User_Model_User::EXCEPTION_EMAIL_EXISTS
            );
        }

        // set confirmation key logic
        if ($user->getForceConfirmed()) {
            $user->setConfirmation(null);
        } elseif (!$user->getId() && $user->isConfirmationRequired()) {
            $user->setConfirmation($user->getRandomConfirmationKey());
        }
        // remove user confirmation key from database, if empty
        if (!$user->getConfirmation()) {
            $user->setConfirmation(null);
        }

        return $this;
    }

    /**
     * Retrieve select object for loading base entity row
     *
     * @param Varien_Object $object
     * @param mixed $rowId
     * @return Varien_Db_Select
     */
    protected function _getLoadRowSelect($object, $rowId)
    {
        $select = parent::_getLoadRowSelect($object, $rowId);
        if ($object->getWebsiteId() && $object->getSharingConfig()->isWebsiteScope()) {
            $select->where('website_id =?', (int)$object->getWebsiteId());
        }

        return $select;
    }

    /**
     * Load user by email
     *
     * @throws Mage_Core_Exception
     *
     * @param Aduroware_User_Model_User $user
     * @param string $email
     * @param bool $testOnly
     * @return Aduroware_User_Model_Resource_User
     */
    public function loadByEmail(Aduroware_User_Model_User $user, $email, $testOnly = false)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('user_email' => $email);
        $select  = $adapter->select()
            ->from($this->getEntityTable(), array($this->getEntityIdField()))
            ->where('email = :user_email');

        if ($user->getSharingConfig()->isWebsiteScope()) {
            if (!$user->hasData('website_id')) {
                Mage::throwException(
                    Mage::helper('user')->__('User website ID must be specified when using the website scope')
                );
            }
            $bind['website_id'] = (int)$user->getWebsiteId();
            $select->where('website_id = :website_id');
        }

        $userId = $adapter->fetchOne($select, $bind);
        if ($userId) {
            $this->load($user, $userId);
        } else {
            $user->setData(array());
        }

        return $this;
    }

    /**
     * Change user password
     *
     * @param Aduroware_User_Model_User $user
     * @param string $newPassword
     * @return Aduroware_User_Model_Resource_User
     */
    public function changePassword(Aduroware_User_Model_User $user, $newPassword)
    {
        $user->setPassword($newPassword);
        $this->saveAttribute($user, 'password_hash');
        return $this;
    }

    /**
     * Check whether there are email duplicates of users in global scope
     *
     * @return bool
     */
    public function findEmailDuplicates()
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('user/entity'), array('email', 'cnt' => 'COUNT(*)'))
            ->group('email')
            ->order('cnt DESC')
            ->limit(1);
        $lookup = $adapter->fetchRow($select);
        if (empty($lookup)) {
            return false;
        }
        return $lookup['cnt'] > 1;
    }

    /**
     * Check user by id
     *
     * @param int $userId
     * @return bool
     */
    public function checkUserId($userId)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('entity_id' => (int)$userId);
        $select  = $adapter->select()
            ->from($this->getTable('user/entity'), 'entity_id')
            ->where('entity_id = :entity_id')
            ->limit(1);

        $result = $adapter->fetchOne($select, $bind);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * Get user website id
     *
     * @param int $userId
     * @return int
     */
    public function getWebsiteId($userId)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('entity_id' => (int)$userId);
        $select  = $adapter->select()
            ->from($this->getTable('user/entity'), 'website_id')
            ->where('entity_id = :entity_id');

        return $adapter->fetchOne($select, $bind);
    }

    /**
     * Custom setter of increment ID if its needed
     *
     * @param Varien_Object $object
     * @return Aduroware_User_Model_Resource_User
     */
    public function setNewIncrementId(Varien_Object $object)
    {
        if (Mage::getStoreConfig(Aduroware_User_Model_User::XML_PATH_GENERATE_HUMAN_FRIENDLY_ID)) {
            parent::setNewIncrementId($object);
        }
        return $this;
    }

    /**
     * Change reset password link token
     *
     * Stores new reset password link token and its creation time
     *
     * @param Aduroware_User_Model_User $newResetPasswordLinkToken
     * @param string $newResetPasswordLinkToken
     * @return Aduroware_User_Model_Resource_User
     */
    public function changeResetPasswordLinkToken(Aduroware_User_Model_User $user, $newResetPasswordLinkToken) {
        if (is_string($newResetPasswordLinkToken) && !empty($newResetPasswordLinkToken)) {
            $user->setRpToken($newResetPasswordLinkToken);
            $currentDate = Varien_Date::now();
            $user->setRpTokenCreatedAt($currentDate);
            $this->saveAttribute($user, 'rp_token');
            $this->saveAttribute($user, 'rp_token_created_at');
        }
        return $this;
    }
}

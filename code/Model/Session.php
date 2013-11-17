<?php
class Aduroware_User_Model_Session extends Mage_Core_Model_Session_Abstract
{
    protected $_user;

    protected $_isUserIdChecked = null;

    protected $_persistentUserGroupId = null;

    public function __construct()
    {
        $this->init('user');
        Mage::dispatchEvent('user_session_init', array('user_session'=>$this));
    }

    public function setUser(Aduroware_User_Model_User $user)
    {
        // check if user is not confirmed
        if ($user->isConfirmationRequired()) {
            if ($user->getConfirmation()) {
                return $this->_logout();
            }
        }
        $this->_user = $user;
        $this->setId($user->getId());
        // save user as confirmed, if it is not
        if ((!$user->isConfirmationRequired()) && $user->getConfirmation()) {
            $user->setConfirmation(null)->save();
            $user->setIsJustConfirmed(true);
        }
        return $this;
    }

    public function getUser()
    {
        if ($this->_user instanceof Aduroware_User_Model_User) {
            return $this->_user;
        }

        $user = Mage::getModel('user/user')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        if ($this->getId()) {
            $user->load($this->getId());
        }

        $this->setUser($user);
        return $this->_user;
    }

    public function setUserId($id)
    {
        $this->setData('user_id', $id);
        return $this;
    }

    public function getUserId()
    {
        if ($this->getData('user_id')) {
            return $this->getData('user_id');
        }
        return ($this->isLoggedIn()) ? $this->getId() : null;
    }

    public function setUserGroupId($id)
    {
        $this->setData('user_group_id', $id);
        return $this;
    }

    public function getUserGroupId()
    {
        if ($this->getData('user_group_id')) {
            return $this->getData('user_group_id');
        }
        if ($this->isLoggedIn() && $this->getUser()) {
            return $this->getUser()->getGroupId();
        }
        return Aduroware_User_Model_Group::NOT_LOGGED_IN_ID;
    }

    public function isLoggedIn()
    {
        return (bool)$this->getId() && (bool)$this->checkUserId($this->getId());
    }

    public function checkUserId($userId)
    {
        if ($this->_isUserIdChecked === null) {
            $this->_isUserIdChecked = Mage::getResourceSingleton('user/user')->checkUserId($userId);
        }
        return $this->_isUserIdChecked;
    }

    public function login($username, $password)
    {
        $user = Mage::getModel('user/user')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

        if ($user->authenticate($username, $password)) {
            $this->setUserAsLoggedIn($user);
            $this->renewSession();
            return true;
        }
        return false;
    }

    public function setUserAsLoggedIn($user)
    {
        $this->setUser($user);
        Mage::dispatchEvent('user_login', array('user'=>$user));
        return $this;
    }

    public function loginById($userId)
    {
        $user = Mage::getModel('user/user')->load($userId);
        if ($user->getId()) {
            $this->setUserAsLoggedIn($user);
            return true;
        }
        return false;
    }

    public function logout()
    {
        if ($this->isLoggedIn()) {
            Mage::dispatchEvent('user_logout', array('user' => $this->getUser()) );
            $this->_logout();
        }
        return $this;
    }

    public function authenticate(Mage_Core_Controller_Varien_Action $action, $loginUrl = null)
    {
        if ($this->isLoggedIn()) {
            return true;
        }

        $this->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_current' => true)));
        if (isset($loginUrl)) {
            $action->getResponse()->setRedirect($loginUrl);
        } else {
            $action->setRedirectWithCookieCheck(Aduroware_User_Helper_Data::ROUTE_ACCOUNT_LOGIN,
                Mage::helper('user')->getLoginUrlParams()
            );
        }

        return false;
    }

    protected function _setAuthUrl($key, $url)
    {
        $url = Mage::helper('core/url')
            ->removeRequestParam($url, Mage::getSingleton('core/session')->getSessionIdQueryParam());
        // Add correct session ID to URL if needed
        $url = Mage::getModel('core/url')->getRebuiltUrl($url);
        return $this->setData($key, $url);
    }

    protected function _logout()
    {
        $this->setId(null);
        $this->setUserGroupId(Aduroware_User_Model_Group::NOT_LOGGED_IN_ID);
        $this->getCookie()->delete($this->getSessionName());
        return $this;
    }

    public function setBeforeAuthUrl($url)
    {
        return $this->_setAuthUrl('before_auth_url', $url);
    }

    public function setAfterAuthUrl($url)
    {
        return $this->_setAuthUrl('after_auth_url', $url);
    }

    public function renewSession()
    {
        parent::renewSession();
        Mage::getSingleton('core/session')->unsSessionHosts();

        return $this;
    }
}

<?php
class Aduroware_User_AccountController extends Mage_Core_Controller_Front_Action
{
    protected $_cookieCheckActions = array('loginPost', 'createpost');

    protected function _getSession()
    {
        return Mage::getSingleton('user/session');
    }

    public function preDispatch()
    {
Mage::log(__METHOD__.": you're in!");
        // a brute-force protection here would be nice

        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = $this->getRequest()->getActionName();
        $openActions = array(
            'create',
            'login',
            'logoutsuccess',
            'forgotpassword',
            'forgotpasswordpost',
            'resetpassword',
            'resetpasswordpost',
            'confirm',
            'confirmation'
        );
        $pattern = '/^(' . implode('|', $openActions) . ')/i';

        if (!preg_match($pattern, $action)) {
            if (!$this->_getSession()->authenticate($this)) {
                $this->setFlag('', 'no-dispatch', true);
            }
        } else {
            $this->_getSession()->setNoReferer(true);
        }
    }

    public function postDispatch()
    {
        parent::postDispatch();
        $this->_getSession()->unsNoReferer(false);
    }

    public function indexAction()
    {
Mage::log(__METHOD__.": you're in!");
        $this->loadLayout();
        $this->_initLayoutMessages('user/session');

        $this->getLayout()->getBlock('content')->append(
            $this->getLayout()->createBlock('user/account_dashboard')
        );
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Account'));
        $this->renderLayout();
    }

    public function loginAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $this->getResponse()->setHeader('Login-Required', 'true');
        $this->loadLayout();
        $this->_initLayoutMessages('user/session');
        $this->renderLayout();
    }

    public function loginPostAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    if ($session->getUser()->getIsJustConfirmed()) {
                        $this->_welcomeUser($session->getUser(), true);
                    }
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Aduroware_User_Model_User::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value = Mage::helper('user')->getEmailConfirmationUrl($login['username']);
                            $message = Mage::helper('user')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
                            break;
                        case Aduroware_User_Model_User::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose user password
                }
            } else {
                $session->addError($this->__('Login and password are required.'));
            }
        }

        $this->_loginPostRedirect();
    }

    protected function _loginPostRedirect()
    {
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {
            // Set default URL to redirect user to
            $session->setBeforeAuthUrl(Mage::helper('user')->getAccountUrl());
            // Redirect user to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag(
                    Aduroware_User_Helper_Data::XML_PATH_USER_STARTUP_REDIRECT_TO_DASHBOARD
                )) {
                    $referer = $this->getRequest()->getParam(Aduroware_User_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        // Rebuild referer URL to handle the case when SID was changed
                        $referer = Mage::getModel('core/url')
                            ->getRebuiltUrl(Mage::helper('core')->urlDecode($referer));
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl(Mage::helper('user')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() == Mage::helper('user')->getLogoutUrl()) {
            $session->setBeforeAuthUrl(Mage::helper('user')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        $this->_redirectUrl($session->getBeforeAuthUrl(true));
    }

    public function logoutAction()
    {
        $this->_getSession()->logout()
            ->setBeforeAuthUrl(Mage::getUrl());

        $this->_redirect('*/*/logoutSuccess');
    }

    public function logoutSuccessAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function createAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*');
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('user/session');
        $this->renderLayout();
    }

    public function createPostAction()
    {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session->setEscapeMessages(true); // prevent XSS injection in user input
        if ($this->getRequest()->isPost()) {
            $errors = array();

            if (!$user = Mage::registry('current_user')) {
                $user = Mage::getModel('user/user')->setId(null);
            }

            $userForm = Mage::getModel('user/form');
            $userForm->setFormCode('user_account_create')
                ->setEntity($user);

            $userData = $userForm->extractData($this->getRequest());

            if ($this->getRequest()->getParam('is_subscribed', false)) {
                $user->setIsSubscribed(1);
            }

            $user->getGroupId();

            try {
                $userErrors = $userForm->validateData($userData);
                if ($userErrors !== true) {
                    $errors = array_merge($userErrors, $errors);
                } else {
                    $userForm->compactData($userData);
                    $user->setPassword($this->getRequest()->getPost('password'));
                    $user->setConfirmation($this->getRequest()->getPost('confirmation'));
                    $userErrors = $user->validate();
                    if (is_array($userErrors)) {
                        $errors = array_merge($userErrors, $errors);
                    }
                }

                $validationResult = count($errors) == 0;

                if (true === $validationResult) {
                    $user->save();

                    Mage::dispatchEvent('user_register_success',
                        array('account_controller' => $this, 'user' => $user)
                    );

                    if ($user->isConfirmationRequired()) {
                        $user->sendNewAccountEmail(
                            'confirmation',
                            $session->getBeforeAuthUrl(),
                            Mage::app()->getStore()->getId()
                        );
                        $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.', Mage::helper('user')->getEmailConfirmationUrl($user->getEmail())));
                        $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
                        return;
                    } else {
                        $session->setUserAsLoggedIn($user);
                        $url = $this->_welcomeUser($user);
                        $this->_redirectSuccess($url);
                        return;
                    }
                } else {
                    $session->setUserFormData($this->getRequest()->getPost());
                    if (is_array($errors)) {
                        foreach ($errors as $errorMessage) {
                            $session->addError($errorMessage);
                        }
                    } else {
                        $session->addError($this->__('Invalid user data'));
                    }
                }
            } catch (Mage_Core_Exception $e) {
                $session->setUserFormData($this->getRequest()->getPost());
                if ($e->getCode() === Aduroware_User_Model_User::EXCEPTION_EMAIL_EXISTS) {
                    $url = Mage::getUrl('user/account/forgotpassword');
                    $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
                    $session->setEscapeMessages(false);
                } else {
                    $message = $e->getMessage();
                }
                $session->addError($message);
            } catch (Exception $e) {
                $session->setUserFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the user.'));
            }
        }

        $this->_redirectError(Mage::getUrl('*/*/create', array('_secure' => true)));
    }

    protected function _welcomeUser(Aduroware_User_Model_User $user, $isJustConfirmed = false)
    {
        $this->_getSession()->addSuccess(
            $this->__('Thank you for registering with %s.', Mage::app()->getStore()->getFrontendName())
        );

        $user->sendNewAccountEmail(
            $isJustConfirmed ? 'confirmed' : 'registered',
            '',
            Mage::app()->getStore()->getId()
        );

        $successUrl = Mage::getUrl('*/*/index', array('_secure'=>true));
        if ($this->_getSession()->getBeforeAuthUrl()) {
            $successUrl = $this->_getSession()->getBeforeAuthUrl(true);
        }
        return $successUrl;
    }

    public function confirmAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        try {
            $id      = $this->getRequest()->getParam('id', false);
            $key     = $this->getRequest()->getParam('key', false);
            $backUrl = $this->getRequest()->getParam('back_url', false);
            if (empty($id) || empty($key)) {
                throw new Exception($this->__('Bad request.'));
            }

            // load user by id (try/catch in case if it throws exceptions)
            try {
                $user = Mage::getModel('user/user')->load($id);
                if ((!$user) || (!$user->getId())) {
                    throw new Exception('Failed to load user by id.');
                }
            }
            catch (Exception $e) {
                throw new Exception($this->__('Wrong user account specified.'));
            }

            // check if it is inactive
            if ($user->getConfirmation()) {
                if ($user->getConfirmation() !== $key) {
                    throw new Exception($this->__('Wrong confirmation key.'));
                }

                // activate user
                try {
                    $user->setConfirmation(null);
                    $user->save();
                }
                catch (Exception $e) {
                    throw new Exception($this->__('Failed to confirm user account.'));
                }

                // log in and send greeting email, then die happy
                $this->_getSession()->setUserAsLoggedIn($user);
                $successUrl = $this->_welcomeUser($user, true);
                $this->_redirectSuccess($backUrl ? $backUrl : $successUrl);
                return;
            }

            // die happy
            $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
            return;
        }
        catch (Exception $e) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError(Mage::getUrl('*/*/index', array('_secure'=>true)));
            return;
        }
    }

    public function confirmationAction()
    {
        $user = Mage::getModel('user/user');
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        // try to confirm by email
        $email = $this->getRequest()->getPost('email');
        if ($email) {
            try {
                $user->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
                if (!$user->getId()) {
                    throw new Exception('');
                }
                if ($user->getConfirmation()) {
                    $user->sendNewAccountEmail('confirmation', '', Mage::app()->getStore()->getId());
                    $this->_getSession()->addSuccess($this->__('Please, check your email for confirmation key.'));
                } else {
                    $this->_getSession()->addSuccess($this->__('This email does not require confirmation.'));
                }
                $this->_getSession()->setUsername($email);
                $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure' => true)));
            } catch (Exception $e) {
                $this->_getSession()->addException($e, $this->__('Wrong email.'));
                $this->_redirectError(Mage::getUrl('*/*/*', array('email' => $email, '_secure' => true)));
            }
            return;
        }

        // output form
        $this->loadLayout();

        $this->getLayout()->getBlock('accountConfirmation')
            ->setEmail($this->getRequest()->getParam('email', $email));

        $this->_initLayoutMessages('user/session');
        $this->renderLayout();
    }

    public function forgotPasswordAction()
    {
        $this->loadLayout();

        $this->getLayout()->getBlock('forgotPassword')->setEmailValue(
            $this->_getSession()->getForgottenEmail()
        );
        $this->_getSession()->unsForgottenEmail();

        $this->_initLayoutMessages('user/session');
        $this->renderLayout();
    }

    public function forgotPasswordPostAction()
    {
        $email = (string) $this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }

            $user = Mage::getModel('user/user')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($user->getId()) {
                try {
                    $newResetPasswordLinkToken = Mage::helper('user')->generateResetPasswordLinkToken();
                    $user->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $user->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                    $this->_getSession()->addError($exception->getMessage());
                    $this->_redirect('*/*/forgotpassword');
                    return;
                }
            }
            $this->_getSession()
                ->addSuccess(Mage::helper('user')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('user')->htmlEscape($email)));
            $this->_redirect('*/*/');
            return;
        } else {
            $this->_getSession()->addError($this->__('Please enter your email.'));
            $this->_redirect('*/*/forgotpassword');
            return;
        }
    }

    public function resetPasswordAction()
    {
        $resetPasswordLinkToken = (string) $this->getRequest()->getQuery('token');
        $userId = (int) $this->getRequest()->getQuery('id');
        try {
            $this->_validateResetPasswordLinkToken($userId, $resetPasswordLinkToken);
            $this->loadLayout();
            // Pass received parameters to the reset forgotten password form
            $this->getLayout()->getBlock('resetPassword')
                ->setUserId($userId)
                ->setResetPasswordLinkToken($resetPasswordLinkToken);
            $this->renderLayout();
        } catch (Exception $exception) {
            $this->_getSession()->addError(Mage::helper('user')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/forgotpassword');
        }
    }

    public function resetPasswordPostAction()
    {
        $resetPasswordLinkToken = (string) $this->getRequest()->getQuery('token');
        $userId = (int) $this->getRequest()->getQuery('id');
        $password = (string) $this->getRequest()->getPost('password');
        $passwordConfirmation = (string) $this->getRequest()->getPost('confirmation');

        try {
            $this->_validateResetPasswordLinkToken($userId, $resetPasswordLinkToken);
        } catch (Exception $exception) {
            $this->_getSession()->addError(Mage::helper('user')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/');
            return;
        }

        $errorMessages = array();
        if (iconv_strlen($password) <= 0) {
            array_push($errorMessages, Mage::helper('user')->__('New password field cannot be empty.'));
        }
        $user = Mage::getModel('user/user')->load($userId);

        $user->setPassword($password);
        $user->setConfirmation($passwordConfirmation);
        $validationErrorMessages = $user->validate();
        if (is_array($validationErrorMessages)) {
            $errorMessages = array_merge($errorMessages, $validationErrorMessages);
        }

        if (!empty($errorMessages)) {
            $this->_getSession()->setUserFormData($this->getRequest()->getPost());
            foreach ($errorMessages as $errorMessage) {
                $this->_getSession()->addError($errorMessage);
            }
            $this->_redirect('*/*/resetpassword', array(
                'id' => $userId,
                'token' => $resetPasswordLinkToken
            ));
            return;
        }

        try {
            // Empty current reset password token i.e. invalidate it
            $user->setRpToken(null);
            $user->setRpTokenCreatedAt(null);
            $user->setConfirmation(null);
            $user->save();
            $this->_getSession()->addSuccess(Mage::helper('user')->__('Your password has been updated.'));
            $this->_redirect('*/*/login');
        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot save a new password.'));
            $this->_redirect('*/*/resetpassword', array(
                'id' => $userId,
                'token' => $resetPasswordLinkToken
            ));
            return;
        }
    }

    protected function _validateResetPasswordLinkToken($userId, $resetPasswordLinkToken)
    {
        if (!is_int($userId)
            || !is_string($resetPasswordLinkToken)
            || empty($resetPasswordLinkToken)
            || empty($userId)
            || $userId < 0
        ) {
            throw Mage::exception('Mage_Core', Mage::helper('user')->__('Invalid password reset token.'));
        }

        $user = Mage::getModel('user/user')->load($userId);
        if (!$user || !$user->getId()) {
            throw Mage::exception('Mage_Core', Mage::helper('user')->__('Wrong user account specified.'));
        }

        $userToken = $user->getRpToken();
        if (strcmp($userToken, $resetPasswordLinkToken) != 0 || $user->isResetPasswordLinkTokenExpired()) {
            throw Mage::exception('Mage_Core', Mage::helper('user')->__('Your password reset link has expired.'));
        }
    }

    public function editAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('user/session');

        $block = $this->getLayout()->getBlock('user_edit');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $data = $this->_getSession()->getUserFormData(true);
        $user = $this->_getSession()->getUser();
        if (!empty($data)) {
            $user->addData($data);
        }
        if ($this->getRequest()->getParam('changepass')==1){
            $user->setChangePassword(1);
        }

        $this->getLayout()->getBlock('head')->setTitle($this->__('Account Information'));
        $this->getLayout()->getBlock('messages')->setEscapeMessageFlag(true);
        $this->renderLayout();
    }

    public function editPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit');
        }

        if ($this->getRequest()->isPost()) {
            $user = $this->_getSession()->getUser();

            $userForm = Mage::getModel('user/form');
            $userForm->setFormCode('user_account_edit')
                ->setEntity($user);

            $userData = $userForm->extractData($this->getRequest());

            $errors = array();
            $userErrors = $userForm->validateData($userData);
            if ($userErrors !== true) {
                $errors = array_merge($userErrors, $errors);
            } else {
                $userForm->compactData($userData);
                $errors = array();

                // If password change was requested then add it to common validation scheme
                if ($this->getRequest()->getParam('change_password')) {
                    $currPass   = $this->getRequest()->getPost('current_password');
                    $newPass    = $this->getRequest()->getPost('password');
                    $confPass   = $this->getRequest()->getPost('confirmation');

                    $oldPass = $this->_getSession()->getUser()->getPasswordHash();
                    if (Mage::helper('core/string')->strpos($oldPass, ':')) {
                        list($_salt, $salt) = explode(':', $oldPass);
                    } else {
                        $salt = false;
                    }

                    if ($user->hashPassword($currPass, $salt) == $oldPass) {
                        if (strlen($newPass)) {
                            $user->setPassword($newPass);
                            $user->setConfirmation($confPass);
                        } else {
                            $errors[] = $this->__('New password field cannot be empty.');
                        }
                    } else {
                        $errors[] = $this->__('Invalid current password');
                    }
                }

                // Validate account and compose list of errors if any
                $userErrors = $user->validate();
                if (is_array($userErrors)) {
                    $errors = array_merge($errors, $userErrors);
                }
            }

            if (!empty($errors)) {
                $this->_getSession()->setUserFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }

            try {
                $user->setConfirmation(null);
                $user->save();
                $this->_getSession()->setUser($user)
                    ->addSuccess($this->__('The account information has been saved.'));

                $this->_redirect('user/account');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setUserFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setUserFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the user.'));
            }
        }

        $this->_redirect('*/*/edit');
    }

    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('dob'));
        return $data;
    }

}

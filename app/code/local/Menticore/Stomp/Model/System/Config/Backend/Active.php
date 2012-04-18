<?php
class Menticore_Stomp_Model_System_Config_Backend_Active extends Mage_Core_Model_Config_Data
{
    /**
     * Validates connection data given in $fields parameter. Returns true if
     * connection was validated, otherwise false.
     *
     * @param array $fields Fields to validate. Must contain scheme, url, port,
     *                      broker, username and password.
     *
     * @return void
     *
     * @throws Zend_Queue_Exception
     */
    protected function _validateConnection($fields)
    {
        $scheme = $fields['scheme']['value'];
        $host = $fields['host']['value'];
        $port = $fields['port']['value'];
        $broker = $fields['broker']['value'];
        $username = $fields['username']['value'];
        $password = $fields['password']['value'];
        if (preg_match('/^\*+$/', $password)) {
            // Load password from database.
            $password = Mage::getStoreConfig('stomp/general/password');
            $password = Mage::helper('core')->decrypt($password);
        }

        // Try to connect to Stomp server.
        $stomp = Mage::getModel('stomp/stomp');
        $stomp->testConnect($scheme, $host, $port, $broker, $username, $password);
    }

    /**
     * Validate connection before saving is_active = true to database.
     *
     * @return Menticore_Stomp_Model_System_Config_Backend_Active
     *
     * @throws Zend_Queue_Exception
     */
    protected function _beforeSave()
    {
        $params = Mage::app()->getRequest()->getParams();
        $fields = $params['groups']['general']['fields'];
        if ($fields['is_active']['value']) {
            $this->_validateConnection($fields);
        }

        return parent::_beforeSave();
    }
}

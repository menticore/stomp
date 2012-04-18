<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * @package   Menticore_Stomp
 * @author    Menticore
 * @copyright 2012 Menticore
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License (MIT)
 * @link      https://github.com/menticore/stomp
 */

/**
 * Rewrite of Zend Client Connection class to adding debug functionality.
 *
 * @package   Menticore_Stomp
 * @author    Menticore
 * @copyright 2012 Menticore
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License (MIT)
 * @link      https://github.com/menticore/stomp
 */
class Menticore_Stomp_Model_Stomp_Client_Connection
    extends Zend_Queue_Stomp_Client_Connection
{
    /**
     * Check if there is a frame to read.
     *
     * @return boolean
     */
    protected function _canReceive()
    {
        $read = array($this->_socket);
        $write = null;
        $except = null;
        // Timeout of 0.2s is preferred by PHP.
        $canReceive = @stream_select($read, $write, $except, 0, 200000);

        if ($canReceive !== false) {
            $canReceive = count($read);
        } else {
            throw new Zend_Queue_Exception('Check failed to determine if the socket is readable');
        }

        return $canReceive > 0;
    }

    /**
     * Write a frame to the stomp server.
     *
     * @param Zend_Queue_Stom_FrameInterface $frame Frame to send.
     *
     * @return $this
     */
    public function write(Zend_Queue_Stomp_FrameInterface $frame)
    {
        parent::write($frame);
        $debug = Mage::getStoreConfigFlag('stomp/general/debug');
        if ($debug) {
            $output = $frame->toFrame();
            Mage::log("Sent frame:\n" . $output, null, Menticore_Stomp_Helper_Data::DEBUG_FILE);
        }

        return $this;
    }

    /**
     * Reads in a frame from the socket or returns false.
     *
     * @return Zend_Queue_Stomp_FrameInterface|false
     *
     * @throws Zend_Queue_Exception
     */
    public function read()
    {
        if (!$this->_canReceive()) {
            return false;
        }

        $frame = parent::read();
        $debug = Mage::getStoreConfigFlag('stomp/general/debug');
        if ($debug) {
            $output = $frame->toFrame();
            Mage::log("Received frame:\n" . $output, null, Menticore_Stomp_Helper_Data::DEBUG_FILE);
        }

        return $frame;
    }
}

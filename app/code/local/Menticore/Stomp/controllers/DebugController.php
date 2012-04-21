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
 * Debug controller to test functionality.
 *
 * @package   Menticore_Stomp
 * @author    Menticore
 * @copyright 2012 Menticore
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License (MIT)
 * @link      https://github.com/menticore/stomp
 */
class Menticore_Stomp_DebugController extends Mage_Core_Controller_Front_Action
{
    /**
     * Forwards user to noRoute if debugging mode is disabled.
     *
     * @return Menticore_Stomp_DebugController
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $debug = Mage::getStoreConfigFlag('stomp/general/debug');
        if (!$debug) {
            $this->_forward('noRoute');
        }

        return $this;
    }

    /**
     * Tests a stomp connection.
     *
     * @return void
     */
    public function indexAction()
    {
        $stomp = Mage::getModel('stomp/stomp_interface');
        $stomp->connect('tcp', 'localhost', '61613', 'mybroker', 'admin', 'password');
        $stomp->send('/queue/test', 'test');
        $stomp->send('/queue/test', 'muh');
        $stomp->send('/queue/tes', 'lala');
        $stomp->subscribe('/queue/test');

        while ($message = $stomp->receive()) {
            var_dump($message->getBody());
            echo '<br>';
        }

        $stomp->disconnect();

        echo '<br>ok';
    }

    /**
     * Tests a stomp queue.
     *
     * @return void
     */
    public function queueAction()
    {
        $queue = Mage::getModel('stomp/queue');
        $messages = $queue->addMessage('message ' . rand(10, 99))
            ->addMessage('message ' . rand(10, 99))
            ->getMessages();
        var_dump($messages);
    }
}

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
 * Abstract destination model.
 *
 * @package   Menticore_Stomp
 * @author    Menticore
 * @copyright 2012 Menticore
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License (MIT)
 * @link      https://github.com/menticore/stomp
 */
abstract class Menticore_Stomp_Model_DestinationAbstract
{
    /**
     * Stomp connection object.
     *
     * @var Menticore_Stomp_Model_Stomp
     */
    protected $_stomp = null;

    /**
     * Current queue name from system configuration stomp/queue/name.
     *
     * @var string
     */
    protected $_queue = null;

    /**
     * When true, then queue is already subscribed.
     *
     * @var boolean
     */
    protected $_isSubscribed = false;

    /**
     * Constructor opens the connection to the stomp server.
     *
     * @return void
     *
     * @throws Zend_Queue_Exception
     */
    public function __construct()
    {
        $config = Mage::getStoreConfig('stomp/general');
        $this->_stomp = Mage::getModel('stomp/stomp');
        $this->_stomp->connect(
            $config['scheme'],
            $config['host'],
            $config['port'],
            $config['broker'],
            $config['username'],
            Mage::helper('core')->decrypt($config['password'])
        );

        $this->_queue = Mage::getStoreConfig('stomp/queue/name');
    }

    /**
     * Destructor closes the stomp server connection.
     *
     * @return void
     *
     * @throws Zend_Queue_Exception
     */
    public function __destruct()
    {
        $this->_stomp->disconnect();
    }

    /**
     * Returns destination type.
     * Should either return '/queue/', '/topic/' or '/dsub/'.
     *
     * @string
     */
    abstract protected function _getDestination();

    /**
     * Returns all messages from current queue.
     *
     * @return array
     *
     * @throws Zend_Queue_Exception
     */
    public function getMessages()
    {
        if (!$this->_isSubscribed) {
            $this->_stomp->subscribe($this->_getDestination() . $this->_queue);
        }

        $messages = array();
        while ($message = $this->_stomp->receive()) {
            $messages[] = $message->getBody();
        }
        return $messages;
    }

    /**
     * Adds a message to current queue.
     *
     * @param string $message Message to send.
     * @param array  $headers Headers to send (optional).
     *
     * @return Menticore_Stomp_Model_Queue
     *
     * @throws Zend_Queue_Exception
     */
    public function addMessage($message, $headers = array())
    {
        $this->_stomp->send($this->_getDestination() . $this->_queue, $message, $headers);

        return $this;
    }
}

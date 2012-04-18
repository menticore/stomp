<?php
class Menticore_Stomp_Model_Queue
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
     * Returns all messages from current queue.
     *
     * @return array
     *
     * @throws Zend_Queue_Exception
     */
    public function getMessages()
    {
        if (!$this->_isSubscribed) {
            $this->_stomp->subscribe('/queue/' . $this->_queue);
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
        $this->_stomp->send('/queue/' . $this->_queue, $message, $headers);

        return $this;
    }
}

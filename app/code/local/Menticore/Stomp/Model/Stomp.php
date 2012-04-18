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
 * Main class to handle Stomp communication with Stomp protocol
 * http://stomp.codehaus.org/Protocol.
 *
 * @package   Menticore_Stomp
 * @author    Menticore
 * @copyright 2012 Menticore
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License (MIT)
 * @link      https://github.com/menticore/stomp
 */
class Menticore_Stomp_Model_Stomp
{
    /**
     * Used Stomp protocol version.
     */
    const STOMP_PROTOCOL_VERSION = '1.1';

    /**
     * Stomp client.
     *
     * @var Zend_Queue_Stomp_Client
     */
    protected $_client = null;
    protected $_server = null;
    protected $_session = null;
    protected $_version = null;
    protected $_connectionClass = 'Menticore_Stomp_Model_Stomp_Client_Connection';
    protected $_frameClass = 'Menticore_Stomp_Model_Stomp_Frame';

    /**
     * Throws an exception depending on given frame data on read or write access
     * of broker.
     *
     * @param Zend_Queue_Stomp_Frame $frame Frame with command and message information.
     *
     * @return void
     */
    protected function _throwException($frame)
    {
        if ($frame instanceof Zend_Queue_Stomp_Frame) {
            $headers = $frame->getHeaders();
            $message = $headers['message'];
            throw new Zend_Queue_Exception($message);
        } else {
            throw new Zend_Queue_Exception('Frame is not an instance of Zend_Queue_Stomp_Frame.');
        }
    }

    /**
     * Connect to server.
     *
     * @param string $scheme      Scheme of the Stomp server.
     * @param string $host        Host of the Stomp server.
     * @param string $port        Port of the Stomp server.
     * @param string $virtualHost The virtual host in the current broker.
     * @param string $username    Username to connect with (optional).
     * @param string $password    Password to connect with (optional).
     * @param array  $versions    Stomp versions to use (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     */
    protected function _connect($scheme, $host, $port, $virtualHost,
        $username = '', $password = '', $versions = array())
    {
        $client = new Zend_Queue_Stomp_Client($scheme, $host, $port, $this->_connectionClass, $this->_frameClass);
        $frame = $client->createFrame()
            ->setCommand('CONNECT')
            ->setHeader('accept-version', empty($versions) ? self::STOMP_PROTOCOL_VERSION : implode(',', $versions))
            ->setHeader('host', $virtualHost);
        if (!empty($username)) {
            $frame->setHeader('login', $username);
        }
        if (!empty($password)) {
            $frame->setHeader('passcode', $password);
        }

        $client->send($frame);
        $frame = $client->receive();
        if (!$frame || $frame->getCommand() != 'CONNECTED') {
            $this->_throwException($frame);
        }
        $this->_client = $client;
        $this->_server = $frame->getHeader('server');
        $this->_session = $frame->getHeader('session');
        $this->_version = $frame->getHeader('version');

        return $this;
    }

    /**
     * Returns Stomp client. Useful if you want to send requests which are not
     * defined in this class.
     *
     * @return Zend_Queue_Stomp_Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Connect to server.
     *
     * @param string $scheme      Scheme of the Stomp server.
     * @param string $host        Host of the Stomp server.
     * @param string $port        Port of the Stomp server.
     * @param string $virtualHost The virtual host in the current broker.
     * @param string $username    Username to connect with (optional).
     * @param string $password    Password to connect with (optional).
     * @param array  $versions    Stomp versions to use (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     */
    public function connect($scheme, $host, $port, $virtualHost,
        $username = '', $password = '', $versions = array())
    {
        if (!Mage::getStoreConfigFlag('stomp/general/is_active')) {
            throw new Zend_Queue_Exception('Stomp is not activated.');
        }

        $this->_connect($scheme, $host, $port, $virtualHost, $username, $password, $versions);

        return $this;
    }

    /**
     * Tests the connection and closes it afterwards.
     *
     * @param string $scheme      Scheme of the Stomp server.
     * @param string $host        Host of the Stomp server.
     * @param string $port        Port of the Stomp server.
     * @param string $virtualHost The virtual host in the current broker.
     * @param string $username    Username to connect with (optional).
     * @param string $password    Password to connect with (optional).
     * @param array  $versions    Stomp versions to use (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     */
    public function testConnect($scheme, $host, $port, $virtualHost,
        $username = '', $password = '', $versions = array())
    {
        $this->_connect($scheme, $host, $port, $virtualHost, $username, $password, $versions)
            ->disconnect();

        return $this;
    }

    /**
     * Send a message to a destination in the messaging system.
     *
     * @param string $destination Destination to send message, for e.g. "/queue/a".
     * @param string $msg         Message to send.
     * @param array  $headers     Additional headers. Possible headers can be:
     *                              - content-length (default: auto)
     *                              - content-type (default: test/plain)
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     *
     * @todo Add response validation.
     */
    public function send($destination, $msg, $headers = array())
    {
        $client = $this->getClient();

        // Prepare headers.
        $headers['destination'] = $destination;
        if (!isset($headers['content-type'])) {
            $headers['content-type'] = 'text/plain';
        }
        $frame = $client->createFrame()
            ->setCommand('SEND')
            ->setHeaders($headers)
            ->setBody($msg);
        $r = new ReflectionClass($client);
        $client->send($frame);
//        $frame = $client->receive();
//        if (!$frame || $frame->getCommand() != 'CONNECTED') {
//            $this->_throwException($frame);
//        }

        return $this;
    }

    /**
     * Register to listen to a given destination.
     *
     * @param string $destination Destination to send message, for e.g. "/queue/a".
     * @param array  $headers     Additional headers. Possible headers can be:
     *                              - id: Client ID (default: empty)
     *                              - ack: Ack mode. If set to "client", client have to acknowledge
     *                                messages got from the server (default: auto)
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     *
     * @todo Add response validation.
     */
    public function subscribe($destination, $headers = array())
    {
        $client = $this->getClient();
        $headers['destination'] = $destination;
        $headers['id'] = $this->_session;
        $frame = $client->createFrame()
            ->setCommand('SUBSCRIBE')
            ->setHeaders($headers);
        $client->send($frame);
//        $frame = $client->receive();
//        if (!$frame || $frame->getCommand() != 'CONNECTED') {
//            $this->_throwException($frame);
//        }

        return $this;
    }

    /**
     * Remove an existing subscription.
     *
     * @param string $destination Destination to send message, for e.g. "/queue/a".
     * @param array  $headers     Additional headers. Possible headers can be:
     *                              - id: Client ID (default: empty)
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     *
     * @todo Add response validation.
     */
    public function unsubscribe($destination, $headers = array())
    {
        $client = $this->getClient();
        $headers['destination'] = $destination;
        $frame = $client->createFrame()
            ->setCommand('UNSUBSCRIBE')
            ->setHeaders($headers);
        $client->send($frame);
//        $frame = $client->receive();
//        if (!$frame || $frame->getCommand() != 'CONNECTED') {
//            $this->_throwException($frame);
//        }

        return $this;
    }

    /**
     * Acknowledge consumption of a message from a subscription.
     *
     * @param Zend_Queue_Stomp_Frame $message       Message to acknowledge.
     * @param string                 $transactionId Transaction id (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     */
    public function ack(Zend_Queue_Stomp_Frame $message, $transactionId = null)
    {
        $headers = $message->getHeaders();
        if (is_string($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $client = $this->getClient();
        $frame = $client->createFrame()
            ->setCommand('ACK')
            ->setHeaders($headers);
        $client->send($frame);
    }

    /**
     * Don't acknowledge consumption of a message from a subscription.
     *
     * @param Zend_Queue_Stomp_Frame $message       Message to acknowledge.
     * @param string                 $transactionId Transaction id (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     */
    public function nack(Zend_Queue_Stomp_Frame $message, $transactionId = null)
    {
        $headers = $message->getHeaders();
        if (is_string($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $client = $this->getClient();
        $frame = $client->createFrame()
            ->setCommand('NACK')
            ->setHeaders($headers);
        $client->send($frame);
    }

    /**
     * Start a transaction.
     *
     * @param string $transactionId Transaction ID (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     *
     * @todo Add response validation.
     */
    public function begin($transactionId = null)
    {
        $client = $this->getClient();
        $headers = array();
        if (is_string($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $frame = $client->createFrame()
            ->setCommand('BEGIN')
            ->setHeaders($headers);
        $client->send($frame);
//        $frame = $client->receive();
//        if (!$frame || $frame->getCommand() != 'CONNECTED') {
//            $this->_throwException($frame);
//        }
        return $this;
    }

    /**
     * Commit a transaction in progress.
     *
     * @param string $transactionId Transaction ID (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     *
     * @todo Add response validation.
     */
    public function commit($transactionId = null)
    {
        $client = $this->getClient();
        $headers = array();
        if (is_string($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $frame = $client->createFrame()
            ->setCommand('COMMIT')
            ->setHeaders($headers);
        $client->send($frame);
//        $frame = $client->receive();
//        if (!$frame || $frame->getCommand() != 'CONNECTED') {
//            $this->_throwException($frame);
//        }
        return $this;
    }

    /**
     * Rollback a transaction in progress.
     *
     * @param string $transactionId Transaction ID (optional).
     *
     * @return Menticore_Stomp_Model_Stomp
     *
     * @throws Zend_Queue_Exception
     *
     * @todo Add response validation.
     */
    public function abort($transactionId = null)
    {
        $client = $this->getClient();
        $headers = array();
        if (is_string($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $frame = $client->createFrame()
            ->setCommand('ABORT')
            ->setHeaders($headers);
        $client->send($frame);
//        $frame = $client->receive();
//        if (!$frame || $frame->getCommand() != 'CONNECTED') {
//            $this->_throwException($frame);
//        }
        return $this;
    }

    /**
     * Graceful disconnect from the server.
     *
     * @return Menticore_Stomp_Model_Stomp
     */
    public function disconnect()
    {
        if ($this->getClient()) {
            $client = $this->getClient();
            $frame = $client->createFrame()
                ->setCommand('DISCONNECT');
            $client->send($frame);
            $this->_client = null;
        }

        return $this;
    }

    /**
     * Receive a message from connection.
     *
     * @return Zend_Queue_Stomp_Frame
     */
    public function receive()
    {
        $client = $this->getClient();

        return $client->receive();
    }
}

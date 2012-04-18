<?php
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
        $stomp = Mage::getModel('stomp/stomp');
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

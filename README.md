Menticore Stomp
===============

This package is a free Magento Community module based on the [Stomp protocol](http://stomp.github.com//stomp-specification-1.1.html).



Requirements
------------

* [Magento Community Edition 1.6.0.0](http://www.magentocommerce.com/)
* [Stomp 1.1 Server](http://activemq.apache.org/apollo/)



Installation
------------

Copy the app directory into your Magento shop. Login in Magento backend and set
Stomp configuration in System / Configuration / Services / Stomp.

Clear Magento cache.

That was all! Now you can use the Stomp interface in your shop to communicate
with a Stomp server.



Usage
-----

Queue usage:

    // Initialize queue model.
    $queue = Mage::getModel('stomp/queue');
    // Add some messages to queue. Queue name is given in system configuration.
    $queue->addMessage('message ' . rand(10, 99))
        ->addMessage('message ' . rand(10, 99));
    // Print all messages.
    var_dump($queue->getMessages());



Problems
--------

If you have problems to let Magento communicate with Stomp, try following
steps:

1. Activate debug mode in System / Configuration / Services / Stomp
2. Activate logging in System / Configuration / Advanced / Developer / Log Settings
3. Try to communicate with Stomp.
4. Find log files in directory var/log and debug this files.

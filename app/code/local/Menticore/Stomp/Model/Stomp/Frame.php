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
 * Rewrite of Zend Stomp Frame class to fix a bug in headers. Stomp protocol
 * requires headers in format key:value (without spaces).
 *
 * @package   Menticore_Stomp
 * @author    Menticore
 * @copyright 2012 Menticore
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License (MIT)
 * @link      https://github.com/menticore/stomp
 */
class Menticore_Stomp_Model_Stomp_Frame extends Zend_Queue_Stomp_Frame
{
    /**
     * Takes the current parameters and returns a Stomp Frame.
     *
     * @return string
     *
     * @throws Zend_Queue_Exception
     */
    public function toFrame()
    {
        if ($this->getCommand() === false) {
            throw new Zend_Queue_Exception('You must set the command');
        }

        $command = $this->getCommand();
        $headers = $this->getHeaders();
        $body    = $this->getBody();
        $frame   = '';

        // Add a content-length to the SEND command.
        // @see http://stomp.codehaus.org/Protocol
        if ($this->getAutoContentLength()) {
            $headers[self::CONTENT_LENGTH] = strlen($this->getBody());
        }

        // Command
        $frame = $command . self::EOL;

        // Headers
        foreach ($headers as $key=>$value) {
            $frame .= $key . ':' . $value . self::EOL;
        }

        // Seperator
        $frame .= self::EOL; // Blank line required by protocol.

        // Add the body if any
        if ($body !== false) {
            $frame .= $body;
        }
        $frame .= self::END_OF_FRAME;

        return $frame;
    }
}

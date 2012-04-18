<?php
class Menticore_Stomp_Model_Stomp_Frame extends Zend_Queue_Stomp_Frame
{
    /**
     * Takes the current parameters and returns a Stomp Frame
     *
     * @return string
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

        // add a content-length to the SEND command.
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
        $frame .= self::EOL; // blank line required by protocol

        // add the body if any
        if ($body !== false) {
            $frame .= $body;
        }
        $frame .= self::END_OF_FRAME;

        return $frame;
    }
}

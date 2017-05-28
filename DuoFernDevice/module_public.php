<?

/**
 * Public functions for this module
 *
 */
trait PublicFunction
{
    /**
     * Sends a message to parent io
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return string|boolean response if sent, false if not
     */
    public function SendRawMsgFromDevice(string $msg)
    {
        return $this->SendMsg($msg);
    }
}

?>
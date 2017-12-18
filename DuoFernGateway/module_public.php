<?

/**
 * Public functions for this module
 *
 */
trait PublicFunction
{

    /**
     * Gets status of all devices
     *
     * @return string|boolean response if sent, false if not
     */
    public function GetAllDevicesStatus()
    {
        return $this->SendMsg(DuoFernMessage::DUOFERN_MSG_GET_ALL_DEVICES_STATUS);
    }

    /**
     * Sends a message to parent io
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return string|boolean response if sent, false if not
     */
    public
    function SendRawMsg(string $msg)
    {
        return $this->SendMsg($msg);
    }
}

?>
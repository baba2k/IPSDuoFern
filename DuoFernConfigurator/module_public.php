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
    public function SendRawMsg(string $msg)
    {
        return $this->SendMsg($msg);
    }

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
     *  Pairing device by duo fern code
     *
     * @param string $duoFernCode
     *            duo fern code in hex string in format /^[0-9A-F]{6}$/
     * @return boolean true if paired, false if not
     */
    public function DeviceRemotePair(string $duoFernCode)
    {
        // wait for response time in seconds
        $waitForResponseTime = 5;

        // check valid duo fern code
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
            echo $this->Translate("Invalid DuoFern code (Format: XXXXXX with X = 0-9, A-F)");
            return false;
        }

        //  get device type name
        $duoFernDeviceType = DuoFernDeviceType::getDeviceType(substr($duoFernCode, 0, 2));

        // start pairing mode
        $this->StartPairingMode($waitForResponseTime * 2);

        // start remote pair with device
        IPS_LogMessage(IPS_GetName($this->InstanceID), $this->Translate("Start remote pair with device")
            . " " . ($duoFernDeviceType != false ? $duoFernDeviceType . " ("
                . trim(chunk_split($duoFernCode, 2, " ")) . ")" : trim(chunk_split($duoFernCode, 2, " "))));
        $this->SendRawMsg(preg_replace("/xxxxxx/", $duoFernCode, DuoFernMessage::DUOFERN_MSG_REMOTE_PAIR));

        // wait for paired msg
        $pairedMsg = preg_replace("/xxxxxx/", $duoFernCode, DuoFernMessage::DUOFERN_MSG_PAIRED);
        $pairedMsg = preg_replace("/yy/", "..", $pairedMsg);
        $response = $this->WaitForMsg($pairedMsg, $waitForResponseTime);

        // stop pairing mode
        $this->StopPairingMode();

        return $response;
    }

    /**
     * Starts pairing mode for n seconds
     *
     * @param int $seconds
     *            time the pairing mode is activated in seconds
     * @return boolean true if started, false if not
     */
    public function StartPairingMode(int $seconds)
    {
        $result = $this->SendRawMsg(DuoFernMessage::DUOFERN_MSG_PAIR_START);

        // message not sent
        if ($result == false) {
            return false;
        }

        // start pairing mode
        IPS_LogMessage(IPS_GetName($this->InstanceID), $this->Translate("Start pairing mode..."));

        // start timer
        $this->SetTimerInterval("StopPairingMode", $seconds * 1000);

        return true;
    }

    /**
     * Stops pairing mode
     *
     * @return boolean true if started, false if not
     */
    public function StopPairingMode()
    {
        $result = $this->SendRawMsg(DuoFernMessage::DUOFERN_MSG_PAIR_STOP);

        // message not sent
        if ($result == false) {
            return false;
        }

        // stop timer
        $this->SetTimerInterval("StopPairingMode", 0);

        // start pairing mode
        IPS_LogMessage(IPS_GetName($this->InstanceID), $this->Translate("Stop pairing mode..."));

        return true;
    }

    /**
     * Starts unpairing mode for n seconds
     *
     * @param int $seconds
     *            time the pairing mode is activated in seconds
     * @return boolean true if started, false if not
     */
    public function StartUnpairingMode(int $seconds)
    {
        $result = $this->SendRawMsg(DuoFernMessage::DUOFERN_MSG_UNPAIR_START);

        // message not sent
        if ($result == false) {
            return false;
        }

        // start pairing mode
        IPS_LogMessage(IPS_GetName($this->InstanceID), $this->Translate("Start unpairing mode..."));

        // start timer
        $this->SetTimerInterval("StopUnpairingMode", $seconds * 1000);

        return true;
    }

    /**
     * Stops unpairing mode
     *
     * @return boolean true if started, false if not
     */
    public function StopUnpairingMode()
    {
        $result = $this->SendRawMsg(DuoFernMessage::DUOFERN_MSG_UNPAIR_STOP);

        // message not sent
        if ($result == false) {
            return false;
        }

        // stop timer
        $this->SetTimerInterval("StopUnpairingMode", 0);

        // start pairing mode
        IPS_LogMessage(IPS_GetName($this->InstanceID), $this->Translate("Stop unpairing mode..."));

        return true;
    }

}

?>
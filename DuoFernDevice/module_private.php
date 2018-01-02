<?

/**
 * Private functions for this module
 *
 */
trait PrivateFunction
{
    /**
     * Sends a update children data request to gateway
     * @return string|bool
     */
    private function SendUpdateChildrenData()
    {
        $result = parent::SendDataToParent(json_encode(Array(
            "DataID" => "{D608631B-BABA-4D08-ADB0-5364DD6A2526}",
            "UpdateChildrenData" => utf8_encode("true")
        )));

        return $result;
    }

    /**
     * Writes a value to device
     *
     * @param $ident ident of value
     * @param $value value
     * @return bool true if written, false if not
     */
    private function WriteValue($ident, $value)
    {
        $ident = strtoupper($ident);
        $writeValue = null;
        $duofernCode = $this->ReadPropertyString("duoFernCode");
        $pairTableNumber = $this->PairTableNumberBuffer;
        $msg = null;

        switch ($this->DeviceGroupBuffer) {
            case 22:
                if ($ident == "REMOTE_PAIR") {
                    if ($value === true) {
                        $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_REMOTE_PAIR_START, $duofernCode, $pairTableNumber);
                    } else if ($value === false) {
                        $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_REMOTE_PAIR_UNPAIR_STOP, $duofernCode, $pairTableNumber);
                    }
                } else if ($ident == "REMOTE_UNPAIR") {
                    if ($value === true) {
                        $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_REMOTE_UNPAIR_START, $duofernCode, $pairTableNumber);
                    } else if ($value === false) {
                        $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_REMOTE_PAIR_UNPAIR_STOP, $duofernCode, $pairTableNumber);
                    }
                } else if ($ident == "RESET_DEVICE" && $value === true) {
                    $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_RESET_DEVICE, $duofernCode, $pairTableNumber);
                } else if ($ident == "FULL_RESET_DEVICE" && $value === true) {
                    $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_FULL_RESET_DEVICE, $duofernCode, $pairTableNumber);
                }
                break;
            default:
                trigger_error($this->Translate("Value could not be written"));
                break;
        }

        // no valid msg
        if ($msg === null || !preg_match(DuoFernRegex::DUOFERN_REGEX_MSG, $msg)) {
            trigger_error($this->Translate("Value could not be written"));
            return false;
        }

        // send msg
        $result = $this->SendMsg($msg);

        if ($result !== false) {
            return true;
        } else {
            return false;
        }
    }
}

?>
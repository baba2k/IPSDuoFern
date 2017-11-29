<?
require_once(__DIR__ . DIRECTORY_SEPARATOR . "ips.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "duofern_msg.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "duofern_regex.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "duofern_devicetype.php");

/**
 * Global libraray functions
 */
trait LibraryFunction
{
    use IPSHelpFunction;

    /**
     * Converts a message from hex string to a binary string for sending
     *
     * @param string $hex
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return string data prepared to send to parent
     */
    private function ConvertMsgToSend($hex)
    {
        $str = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $str .= chr(hexdec($hex [$i] . $hex [$i + 1]));
        }

        return $str;
    }

    /**
     * Converts a message from binary string to a hex string for display
     *
     * @param string $str
     *            string data
     * @return string $hex message in hex string in format /^[0-9A-F]{44}$/
     */
    private function ConvertMsgToDisplay($str)
    {
        $hex = "";
        $i = 0;
        do {
            $hex .= sprintf("%02X", ord($str{$i}));
            $i++;
        } while ($i < strlen($str));

        return $hex;
    }

    /**
     * Sends a message to parent gateway
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return string|boolean response if sent, false if not
     */
    private function SendMsg($msg)
    {
        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG, $msg)) {
            return false;
        }

        // convert data from hex to string
        $data = $this->ConvertMsgToSend($msg);

        // send to parent io
        $result = $this->SendDataToParent($data);

        if ($result !== false) {
            $this->SendDebug("RECEIVED RESPONSE", $this->ConvertMsgToSend($result), 1);
        } else {
            $this->SendDebug("TIMEOUT WAITFORRESPONSE", $this->ConvertMsgToSend($this->ExpectedResponse($msg)), 1);
        }

        return $result;
    }

    /**
     * Gets the expected response of a msg
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return boolean|string expected response msg or false on failure
     */
    private function ExpectedResponse($msg)
    {
        // get gateway duo fern code
        $duoFernCode = $this->ReadPropertyString('duoFernCode');

        // generate expected response for msg
        $expectedResponse = DuoFernMessage::GenerateResponse($msg, $duoFernCode);

        return $expectedResponse;
    }
}

?>
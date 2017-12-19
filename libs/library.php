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
            $this->SendDebug("DISCARD TRANSMIT", $this->ConvertMsgToSend($msg), 1);
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);
            return false;
        }

        // convert data from hex to string
        $data = $this->ConvertMsgToSend($msg);

        // send to parent io
        $result = $this->SendDataToParent($data);

        if ($result !== false) {
            $this->SendDebug("RECEIVED RESPONSE", $this->ConvertMsgToSend($result), 1);
        } else { // never entered because of trigger_error in SendDataToParent
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

    /**
     * Waits for an expected message $seconds seconds
     *
     * @param $expectedMsg
     * @param int $seconds
     * @return bool
     */
    private function WaitForMsg($expectedMsg, $seconds = 10)
    {
        // try to get response $seconds times
        for ($i = 0; $i < $seconds * 100; $i++) {
            // get data
            if ($this->SemaphoreEnter('WaitForMsgBuffer')) {
                $waitForResponseBuffer = $this->WaitForMsgBuffer;
                $response = $waitForResponseBuffer->WaitFor($expectedMsg);
                $this->WaitForMsgBuffer = $waitForResponseBuffer;
                $this->SemaphoreLeave('WaitForMsgBuffer');

                if ($response === true) {
                    //$this->SendDebug("RECEIVED WAITFORMSG", $this->ConvertMsgToSend($expectedMsg), 1);
                    return true;
                }
            }

            // wait 10 ms
            IPS_Sleep(10);
        }

        // remove msg from buffer (timeout)
        if ($this->SemaphoreEnter('WaitForMsgBuffer')) {
            $waitForResponseBuffer = $this->WaitForMsgBuffer;
            $waitForResponseBuffer->Remove($expectedMsg, false);
            $this->WaitForMsgBuffer = $waitForResponseBuffer;
            $this->SemaphoreLeave('WaitForMsgBuffer');
        }

        // send expected response as debug msg
        //$this->SendDebug("TIMEOUT WAITFORMSG", $this->ConvertMsgToSend($expectedMsg), 1);

        return false;
    }
}

/**
 * DuoFernWaitForMsgBuffer
 * Buffers expected msgs while waiting for it
 */
class DuoFernWaitForMsgBuffer
{
    public $items = array();

    /**
     * Waits for a msg. if not in items add msg else wait until msg is received
     * @param $msg
     * @return bool
     */
    public function WaitFor($msg)
    {
        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG_WILDCHARS, $msg)) {
            return false;
        }

        foreach ($this->items as $key => $item) {
            if (preg_match('/^' . $item['msg'] . '$/', $msg) && $item['received'] == true) { // received
                unset ($this->items[$key]);
                return true;
            } else if ($item['msg'] == $msg && $item['received'] == false) { // wait
                return false;
            }
        }

        // new message
        $this->items[] = array("msg" => $msg, "received" => false);
        return false;
    }

    /**
     * Sets a msg as received when in items and received = false
     * @param $msg
     * @return bool
     */
    public function Received($msg)
    {
        foreach ($this->items as $key => $item) {
            if (preg_match('/^' . $item['msg'] . '$/', $msg) && $item['received'] == false) {
                $this->items [$key]['received'] = true;
                return true;
            }
        }
        return false;
    }

    /**
     * Removes a msg from items
     * @param $msg
     * @param $received
     * @return bool
     */
    public function Remove($msg, $received)
    {
        foreach ($this->items as $key => $item) {
            if (preg_match('/^' . $item['msg'] . '$/', $msg) && $item['received'] == $received) {
                unset ($this->items[$key]);
                return true;
            }
        }
        return false;
    }
}

?>
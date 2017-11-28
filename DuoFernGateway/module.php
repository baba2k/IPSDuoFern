<?
require_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "library.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_private.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");

/**
 * IPSDuoFern - Control Rademacher DuoFern devices with IP-Symcon
 * Module: DuoFern Gateway
 *
 * @author Sebastian Leicht (baba@baba.tk)
 *
 */
class DuoFernGateway extends IPSModule
{
    /**
     * Module status error codes
     *
     * @var int
     */
    const IS_INVALID_DUOFERN_CODE = IS_EBASE + 1;
    const IS_INIT_FAILED = IS_EBASE + 2;

    /**
     * Traits
     * Includes a group of methods
     */
    use LibraryFunction, PrivateFunction, PublicFunction {
        PrivateFunction::SendMsg insteadof LibraryFunction;
    }
    use MagicGetSetAsBuffer;

    /**
     * Creates module properties
     * Will be called when creating the instance and on starting IP-Symcon
     */
    public function Create()
    {
        parent::Create();

        // register properties
        $this->RegisterPropertyInteger("modus", 0);
        $this->RegisterPropertyString("duoFernCode", $this->RandomGatewayDuoFernCode());

        // register status variables
        $this->RegisterVariableString("DuoFernCode", "DuoFern Code");

        // create buffers
        $this->ReceiveBuffer = "";
        $this->LastReceiveTimestampBuffer = "";
        $this->WaitForResponseBuffer = new DuoFernWaitForResponseBuffer();
        $this->ParentInstanceID = "";
        $this->ChildrenInstanceIDs = array();
    }

    /**
     * Applies the changes
     * Will be called when click on "Apply" at the configuration form or after creating the instance
     */
    public function ApplyChanges()
    {
        // register messages
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);

        // call parent
        parent::ApplyChanges();

        // return when kernel is not ready
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // property modus
        switch ($this->ReadPropertyInteger("modus")) {
            case 0 : // force serial port as parent
                $this->ForceParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
                break;
            case 1 : // force client socket as parent
                $this->ForceParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
                break;
        }

        // property duoFernCode
        $duoFernCode = $this->ReadPropertyString("duoFernCode");
        if (!preg_match(DUOFERN_REGEX_GATEWAY_DUOFERN_CODE, $duoFernCode)) {
            $this->SetStatus(self::IS_INVALID_DUOFERN_CODE);
        } else if ($this->GetStatus() == self::IS_INVALID_DUOFERN_CODE) {
            $this->SetStatus(IS_ACTIVE);
        }

        // set duo fern code status variable
        $this->UpdateValueByIdent("DuoFernCode", $duoFernCode);

        // force refresh
        $this->ForceRefresh();
    }

    /**
     * Receive data from parent
     * Will be called when receiving data from parent
     *
     * @param string $JSONString
     *            data json encoded
     */
    public function ReceiveData($JSONString)
    {
        // decode data
        $data = json_decode($JSONString);

        // get buffers
        $receiveBuffer = $this->ReceiveBuffer;
        $lastReceiveTimestampBuffer = $this->LastReceiveTimestampBuffer;

        // check last receive timestamp, if last receive time > 5s flush buffer
        $lastReceiveSec = time() - $lastReceiveTimestampBuffer;
        if ($lastReceiveSec > 5 && $receiveBuffer != "") {
            $this->SendDebug("FLUSH RECEIVE BUFFER", $receiveBuffer, 1);
            $receiveBuffer = "";
        }

        // add new data at end of receive buffer
        $receiveBuffer .= utf8_decode($data->Buffer);

        // if complete msg in receive buffer
        if (strlen($receiveBuffer) >= 22) {
            // get msg from buffer
            $msg = substr($receiveBuffer, 0, 22);
            $displayMsg = $this->ConvertMsgToDisplay($msg);

            // remove msg from receive buffer
            $receiveBuffer = substr($receiveBuffer, 22);

            // ack / response msg
            if (preg_match(DUOFERN_REGEX_ACK, $displayMsg)) {
                $waitForResponseBuffer = $this->WaitForResponseBuffer;
                $waitForResponseBuffer->Add($displayMsg);
                $this->WaitForResponseBuffer = $waitForResponseBuffer;
                $this->SendDebug("RECEIVED RESPONSE", $msg, 1);
            } else { // normal msg
                $this->SendDebug("RECEIVED", $msg, 1);

                // forward to devices
                $this->SendDataToChildren(json_encode(Array("DataID" => "{244143D3-BABA-44D4-8740-B997B8F09E50}", "Buffer" => utf8_encode($msg), "DuoFernCode" => substr($displayMsg, 30, 6))));

                // forward to configurator
                $this->SendDataToChildren(json_encode(Array("DataID" => "{15FD9630-BABA-4BCB-90E4-7DE815ECB79D}", "Buffer" => utf8_encode($msg))));
            }

            // send ack
            if (strcmp($displayMsg, DUOFERN_MSG_ACK) !== 0) {
                $this->SendMsg(DUOFERN_MSG_ACK);
            }
        }

        // set new last receive timestamp
        $lastReceiveTimestampBuffer = time();

        // set buffers
        $this->ReceiveBuffer = $receiveBuffer;
        $this->LastReceiveTimestampBuffer = $lastReceiveTimestampBuffer;
    }

    /**
     * Forwards data to parent and handles data
     *
     * @param string $JSONString
     * @return string result data
     */
    public function ForwardData($JSONString)
    {
        // decode data
        $data = json_decode($JSONString);

        // get msg
        $msg = $this->ConvertMsgToDisplay(utf8_decode($data->Buffer));

        // send to parent io
        $result = $this->SendMsg($msg);

        return $result;
    }

    /**
     * Sends data to parent
     * Will be called from methods ForwardData
     *
     * @param string $Data
     * @return string|bool result data or false if not sent
     */
    protected function SendDataToParent($Data)
    {

        // discard if instance inactive or no active parent
        if (!$this->IsInstanceActive() || !$this->IsParentInstanceActive()) {
            $this->SendDebug("DISCARD TRANSMIT", $Data, 1);
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);
            return false;
        }

        // send to parent io
        $result = parent::SendDataToParent(json_encode(Array(
            "DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}",
            "Buffer" => utf8_encode($Data)
        )));

        // trigger error when msg not sent
        if ($result === false) {
            $this->SendDebug("FAILED TRANSMIT", $Data, 1);
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);
            return false;
        }

        // send msg as debug msg
        if (strcmp($this->ConvertMsgToDisplay($Data), DUOFERN_MSG_ACK) === 0) {
            $this->SendDebug("TRANSMIT ACK", $Data, 1);
        } else {
            $this->SendDebug("TRANSMIT", $Data, 1);
        }

        return $result;
    }

    /**
     * Handles registered messages
     * Will be called when receiving a registered msg
     *
     * @param int $TimeStamp
     * @param int $SenderID
     * @param string $Message
     * @param array $Data
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        IPS_LogMessage("MessageSink", "Message from SenderID " . $SenderID . " with Message " . $Message . "\r\n Data: " . print_r($Data, true));
        switch ($Message) {
            case IPS_KERNELSTARTED :
                $this->ApplyChanges();
                break;
            case FM_CONNECT :
            case FM_DISCONNECT :
                $this->ForceRefresh();
                break;
            case IM_CHANGESTATUS :
                if (($SenderID == @IPS_GetInstance($this->InstanceID) ['ConnectionID']) and ($Data [0] == IS_ACTIVE)) {
                    $this->ForceRefresh();
                }
                break;
            case IM_CHANGESETTINGS :
                // check changed properties
                foreach ($Data as $changedPropertyJson) {
                    $changedProperty = json_decode($changedPropertyJson, true);
                    // if property duoFernCode is changed
                    if (is_array($changedProperty) && array_key_exists("duoFernCode", $changedProperty)) {
                        $duoFernCode = $changedProperty["duoFernCode"];
                        // valid duo fern code
                        if (preg_match(DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
                            $this->ForceRefresh();
                            break;
                        }
                    }
                }
                break;
        }
    }

    /**
     * Configurates the parent io serial port
     * Set and Lock baudrate, stopbits, databits and parity for serial port
     *
     * @return string configuration json string
     */
    public function GetConfigurationForParent()
    {
        return "{\"BaudRate\": \"115200\", \"StopBits\": \"1\", \"DataBits\": \"8\", \"Parity\": \"None\"}";
    }
}

?>
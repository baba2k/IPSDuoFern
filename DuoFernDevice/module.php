<?
require_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "library.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_private.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");

/**
 * IPSDuoFern - Control Rademacher DuoFern devices with IP-Symcon
 * Module: DuoFern Device
 *
 * @author Sebastian Leicht (baba@baba.tk)
 *
 */
class DuoFernDevice extends IPSModule
{
    /**
     * Module status error codes
     *
     * @var int
     */
    const IS_INVALID_DUOFERN_CODE = IS_EBASE + 1;
    const IS_DEVICE_NOT_AVAILABLE = IS_EBASE + 2;

    /**
     * Traits
     * Includes a group of methods
     */
    use LibraryFunction, PrivateFunction, PublicFunction;
    use MagicGetSetAsBuffer;

    /**
     * Creates module properties
     * Will be called when creating the instance and on starting IP-Symcon
     */
    public function Create()
    {
        parent::Create();

        // register properties
        $this->RegisterPropertyString("duoFernCode", "XXXXXX");

        // create buffers
        $this->PairTableNumberBuffer = "FF";
        $this->DeviceGroupBuffer = null;
        $this->VersionBuffer = null;
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
        $this->RegisterMessage($this->InstanceID, IM_CHANGESETTINGS);

        // call parent
        parent::ApplyChanges();

        // return when kernel is not ready
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // require gateway as parent
        $this->ConnectParent("{7AB07511-BABA-418B-81C5-88A7C709D318}");

        // property duoFernCode
        $duoFernCode = $this->ReadPropertyString("duoFernCode");

        // set device group
        $this->DeviceGroupBuffer = DuoFernDeviceType::getDeviceGroup($duoFernCode);

        // set instance status
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
            $this->SetStatus(self::IS_INVALID_DUOFERN_CODE);
            $duoFernCode = "XXXXXX"; // set invalid duoFernCode for receive data filter
        } else if ($this->GetStatus() == self::IS_INVALID_DUOFERN_CODE) {
            $this->SetStatus(IS_ACTIVE);
        }

        // set receive data filter
        $this->SetReceiveDataFilter("(.*" . $duoFernCode . ".*)");
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

        // catch pair table number request from gateway
        if (isset ($data->PairTableNumber)) {
            $this->PairTableNumberBuffer = utf8_decode($data->PairTableNumber);
            return;
        }

        // get msg
        $msg = utf8_decode($data->Buffer);

        // send msg as debug msg
        $this->SendDebug("RECEIVED", $msg, 1);

        $displayMsg = $this->ConvertMsgToDisplay($msg);

        // handle status msg
        if (preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_STATUS, 0, 6) . '.{38}$/', $displayMsg)) {
            $this->VersionBuffer = (string)substr($displayMsg, 24, 1) . "." . (string)substr($displayMsg, 25, 1);
        }

        // set status to active
        $this->SetStatus(IS_ACTIVE);
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
        // discard if invalid duo fern code
        if ($this->GetStatus() == self::IS_INVALID_DUOFERN_CODE) {
            $this->SendDebug("DISCARD TRANSMIT", $Data, 1);

            // trigger error
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);

            return false;
        }

        // send to parent splitter
        $result = parent::SendDataToParent(json_encode(Array(
            "DataID" => "{D608631B-BABA-4D08-ADB0-5364DD6A2526}",
            "Buffer" => utf8_encode($Data)
        )));

        // trigger error when msg not sent
        if ($result == false) {
            $this->SendDebug("FAILED TRANSMIT", $Data, 1);
            // set status to device not available
            $this->SetStatus(self::IS_DEVICE_NOT_AVAILABLE);

            // trigger error
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);

            return false;
        }

        // send msg as debug msg
        if (strcmp($this->ConvertMsgToDisplay($Data), DuoFernMessage::DUOFERN_MSG_ACK) === 0) {
            $this->SendDebug("TRANSMIT ACK", $Data, 1);
        } else {
            $this->SendDebug("TRANSMIT", $Data, 1);
        }

        // set status active
        $this->SetStatus(IS_ACTIVE);

        return $result;
    }

    /**
     * Sets dynamic configuration form for device list
     *
     * @return string configuration json string
     */
    public function GetConfigurationForm()
    {
        $data = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "form.json"), true);

        // slice not needed elements
        switch ($this->DeviceGroupBuffer) {
            case 22:
                array_splice($data['elements'], 0, 1);
                break;
            default: // not supported device
                array_splice($data['elements'], 1, count($data['elements']) - 5);
                break;
        }

        // set product name
        $duoFernCode = $this->ReadPropertyString("duoFernCode");
        $productName = DuoFernDeviceType::getDeviceType($duoFernCode);
        if ($productName !== null) {
            $data['elements'][count($data['elements']) - 3]['label'] = sprintf($this->Translate("Product name: %s"), $productName);
        } else {
            array_splice($data['elements'], count($data['elements']) - 3, 1);
        }

        // set version
        if ($this->VersionBuffer !== null && $this->GetStatus()) {
            $data['elements'][count($data['elements']) - 1]['label'] = sprintf($this->Translate("Version: %s"), $this->VersionBuffer);
        } else {
            array_splice($data['elements'], count($data['elements']) - 1, 1);
        }

        return json_encode($data);
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
        switch ($Message) {
            case IPS_KERNELSTARTED :
                $this->ApplyChanges();
                break;
            case FM_CONNECT :
                $this->SendUpdateChildrenData();
                break;
            case IM_CHANGESETTINGS :
                // check changed properties
                foreach ($Data as $changedPropertyJson) {
                    $changedProperty = json_decode($changedPropertyJson, true);
                    // if property duoFernCode is changed
                    if (is_array($changedProperty) && array_key_exists("duoFernCode", $changedProperty)) {
                        $this->VersionBuffer = null; // reset version buffer
                        break;
                    }
                }
                break;
        }
    }
}

?>
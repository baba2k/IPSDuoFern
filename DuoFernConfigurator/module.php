<?
require_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "library.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_private.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");

/**
 * IPSDuofern - Control Rademacher DuoFern devices with IP-Symcon
 * Module: DuoFern Configurator
 *
 * @author Sebastian Leicht (baba@baba.tk)
 *
 */
class DuoFernConfigurator extends IPSModule
{
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

        // create buffers
        $this->SeenDevicesBuffer = array();
    }

    /**
     * Applies the changes
     * Will be called when click on "Apply" at the configuration form or after creating the instance
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // require gateway as parent
        $this->ForceParent("{7AB07511-BABA-418B-81C5-88A7C709D318}");
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

        // get msg
        $msg = utf8_decode($data->Buffer);

        // send msg as debug msg
        $this->SendDebug("RECEIVED", $msg, 1);

        // save seen devices with last message timestamp
        $seenDevicesBuffer = (array)$this->SeenDevicesBuffer; // get buffer
        $duoFernCode = (string)substr($this->ConvertMsgToDisplay($msg), 30, 6);
        $seenDevicesBuffer[$duoFernCode] = time();
        $this->SeenDevicesBuffer = $seenDevicesBuffer;
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
        // discard if no active parent
        if (!$this->IsParentInstanceActive()) {
            $this->SendDebug("DISCARD TRANSMIT", $Data, 1);
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);
            return false;
        }

        // send to parent io
        $result = parent::SendDataToParent(json_encode(Array(
            "DataID" => "{D608631B-BABA-4D08-ADB0-5364DD6A2526}",
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
     * Sets dynamic configuration form for device list
     *
     * @return string configuration json string
     */
    public function GetConfigurationForm()
    {
        global $duoFernDeviceTypes;
        $deviceInstanceIds = IPS_GetInstanceListByModuleID("{BE62B172-BABA-4EB1-8C4C-507526645ED5}");
        $parentId = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $deviceList = array();
        $seenDevicesBuffer = (array)$this->SeenDevicesBuffer; // get seen devices buffer

        // add device instances
        foreach ($deviceInstanceIds as $instanceId) {
            // discard if not connected
            if (IPS_GetInstance($instanceId)['ConnectionID'] != $parentId) {
                continue;
            }

            $duoFernCode = IPS_GetProperty($instanceId, "duoFernCode");
            $deviceType = substr($duoFernCode, 0, 2);
            $timeStamp = isset ($seenDevicesBuffer[$duoFernCode]) ? $seenDevicesBuffer[$duoFernCode] : null;
            $timeString = date('Ymd') == date('Ymd', $timeStamp) && $timeStamp != null ?
                date('H:i:s', $timeStamp) : date('d.m.Y H:i:s', $timeStamp);

            $device = [
                "duoFernCode" => chunk_split($duoFernCode, 2, " "),
                "type" => array_key_exists($deviceType, $duoFernDeviceTypes) ? $duoFernDeviceTypes[$deviceType] : $this->Translate("Unknown"),
                "name" => IPS_GetLocation($instanceId),
                "lastMsg" => $timeStamp != null ? $timeString : "N/A",
                "instanceId" => $instanceId,
                "rowColor" => "#C0FFC0"
            ];

            $deviceList[$duoFernCode] = $device;
        }

        // add seen devices
        foreach ($seenDevicesBuffer as $duoFernCode => $lastMessageTimestamp) {
            // skip devices with instance
            if (array_key_exists($duoFernCode, $deviceList)) {
                continue;
            }

            $deviceType = substr($duoFernCode, 0, 2);
            $timeStamp = isset ($seenDevicesBuffer[$duoFernCode]) ? $seenDevicesBuffer[$duoFernCode] : null;
            $timeString = date('Ymd') == date('Ymd', $timeStamp) && $timeStamp != null ?
                date('H:i:s', $timeStamp) : date('d.m.Y H:i:s', $timeStamp);

            $device = [
                "duoFernCode" => chunk_split($duoFernCode, 2, " "),
                "type" => array_key_exists($deviceType, $duoFernDeviceTypes) ? $duoFernDeviceTypes[$deviceType] : $this->Translate("Unknown"),
                "name" => "N/A",
                "lastMsg" => $timeStamp != null ? $timeString : "N/A",
                "instanceId" => "N/A"
            ];

            $deviceList[$duoFernCode] = $device;
        }

        // sort devices bei duo fern code
        ksort($deviceList);

        // get form json as array
        $data = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "form.json"), true);

        // add items to list
        $data['actions'][0]['values'] = array_merge($data['actions'][0]['values'], array_values($deviceList));

        return json_encode($data);
    }
}

?>
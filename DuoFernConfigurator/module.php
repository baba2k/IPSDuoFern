<?
require_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "library.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_private.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");

/**
 * IPSDuoFern - Control Rademacher DuoFern devices with IP-Symcon
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
        $this->WaitForMsgBuffer = new DuoFernWaitForMsgBuffer();

        // create timers
        $this->RegisterTimer("StopPairingMode", 0, 'DUOFERN_StopPairingMode($_IPS["TARGET"]);');
        $this->RegisterTimer("StopUnpairingMode", 0, 'DUOFERN_StopUnpairingMode($_IPS["TARGET"]);');
    }

    /**
     * Applies the changes
     * Will be called when click on "Apply" at the configuration form or after creating the instance
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // require gateway as parent
        $this->ConnectParent("{7AB07511-BABA-418B-81C5-88A7C709D318}");
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

        $displayMsg = $this->ConvertMsgToDisplay($msg);

        // wait for msg buffer
        $waitForMsgBuffer = $this->WaitForMsgBuffer;
        $waitForMsgBuffer->Received($displayMsg);
        $this->WaitForMsgBuffer = $waitForMsgBuffer;

        // save seen devices with last message timestamp
        $seenDevicesBuffer = (array)$this->SeenDevicesBuffer; // get buffer
        $duoFernCode = (string)substr($displayMsg, 30, 6);
        $seenDevicesBuffer[$duoFernCode] = time();
        $this->SeenDevicesBuffer = $seenDevicesBuffer;

        // send paired and unpaired log messages
        $duoFernDeviceType = DuoFernDeviceType::getDeviceType(substr($duoFernCode, 0, 2));
        if (preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_PAIRED, 0, 6) . '.{38}$/', $displayMsg)) {
            IPS_LogMessage(IPS_GetName($this->InstanceID), sprintf($this->Translate("Device %s paired"),
                $duoFernDeviceType != false ? $duoFernDeviceType . " (" . trim(chunk_split($duoFernCode, 2, " ")) . ")" : trim(chunk_split($duoFernCode, 2, " "))));
        } else if (preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_UNPAIRED, 0, 6) . '.{38}$/', $displayMsg)) {
            IPS_LogMessage(IPS_GetName($this->InstanceID), sprintf($this->Translate("Device %s unpaired"),
                $duoFernDeviceType != false ? $duoFernDeviceType . " (" . trim(chunk_split($duoFernCode, 2, " ")) . ")" : trim(chunk_split($duoFernCode, 2, " "))));
        } else if (preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_ALREADY_UNPAIRED, 0, 6) . '.{38}$/', $displayMsg)) {
            IPS_LogMessage(IPS_GetName($this->InstanceID), sprintf($this->Translate("Device %s already unpaired"),
                $duoFernDeviceType != false ? $duoFernDeviceType . " (" . trim(chunk_split($duoFernCode, 2, " ")) . ")" : trim(chunk_split($duoFernCode, 2, " "))));
        }
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
        // send to parent splitter
        $result = parent::SendDataToParent(json_encode(Array(
            "DataID" => "{D608631B-BABA-4D08-ADB0-5364DD6A2526}",
            "Buffer" => utf8_encode($Data)
        )));

        // trigger error when msg not sent
        if ($result == false) {
            $this->SendDebug("FAILED TRANSMIT", $Data, 1);
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);
            return false;
        }

        // send msg as debug msg
        if (strcmp($this->ConvertMsgToDisplay($Data), DuoFernMessage::DUOFERN_MSG_ACK) === 0) {
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
            $deviceTypeCode = substr($duoFernCode, 0, 2);
            $deviceType = DuoFernDeviceType::getDeviceType($deviceTypeCode);
            $timeStamp = isset ($seenDevicesBuffer[$duoFernCode]) ? $seenDevicesBuffer[$duoFernCode] : null;
            $timeString = date('Ymd') == date('Ymd', $timeStamp) && $timeStamp != null ?
                date('H:i:s', $timeStamp) : date('d.m.Y H:i:s', $timeStamp);

            $device = [
                "duoFernCode" => trim(chunk_split($duoFernCode, 2, " ")),
                "type" => $deviceType != null ? $deviceType : $this->Translate("Unknown"),
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

            $deviceTypeCode = substr($duoFernCode, 0, 2);
            $deviceType = DuoFernDeviceType::getDeviceType($deviceTypeCode);
            $timeStamp = isset ($seenDevicesBuffer[$duoFernCode]) ? $seenDevicesBuffer[$duoFernCode] : null;
            $timeString = date('Ymd') == date('Ymd', $timeStamp) && $timeStamp != null ?
                date('H:i:s', $timeStamp) : date('d.m.Y H:i:s', $timeStamp);

            $device = [
                "duoFernCode" => trim(chunk_split($duoFernCode, 2, " ")),
                "type" => $deviceType != null ? $deviceType : $this->Translate("Unknown"),
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

        // set devices count
        $data['actions'][1]['label'] = sprintf($this->Translate("Devices: %d"), count($deviceList));

        // add items to list
        $data['actions'][2]['values'] = array_values($deviceList);

        // define onClick method for create instance button
        $data['actions'][3]['onClick'] = <<< 'EOT'
                // define helper function to translate
                function Translate($string)
                {
                    $translations = json_decode(file_get_contents(IPS_GetKernelDir()
                        . DIRECTORY_SEPARATOR . "modules"
                        . DIRECTORY_SEPARATOR . "IPSDuoFern"
                        . DIRECTORY_SEPARATOR . "DuoFernConfigurator"
                        . DIRECTORY_SEPARATOR . "locale.json"), true);
                    $translations = $translations["translations"]["de"];

                    // found translation
                    if (array_key_exists($string, $translations)) {
                        return $translations[$string];
                    }

                    // do not translate
                    return $string;
                }

                // check device has already an instance
                if ($deviceList == null) {
                    echo Translate("No DuoFern device selected");
                    return;
                }

                // check device has already an instance
                if ($deviceList['instanceId'] > 0) {
                    echo Translate("Instance already exists");
                    return;
                }

                // create instance
                $instanceId = IPS_CreateInstance('{BE62B172-BABA-4EB1-8C4C-507526645ED5}');
                if ($instanceId == false) {
                    trigger_error(Translate("Instance could not be created") . PHP_EOL, E_USER_ERROR);
                    return;
                }

                // connect instance to actual gateway if not connected
                if (IPS_GetInstance($instanceId)['ConnectionID'] != IPS_GetInstance($id)['ConnectionID']) {
                    if (IPS_GetInstance($instanceId)['ConnectionID'] > 0) {
                        IPS_DisconnectInstance($instanceId);
                    }
                    IPS_ConnectInstance($instanceId, IPS_GetInstance($id)['ConnectionID']);
                }

                // set duo fern code property
                @IPS_SetProperty($instanceId, 'duoFernCode', str_replace(' ', '', $deviceList['duoFernCode']));
                @IPS_ApplyChanges($instanceId);

                // set instance name
                $instanceName = 'DuoFern ' . $deviceList['type'] . ' (' . $deviceList['duoFernCode'] . ')' ;
                IPS_SetName($instanceId, $instanceName);

                echo Translate("Instance created") . ": " .  $instanceName;
EOT;

        // define onClick method for search devices button
        $data['actions'][4]['onClick'] = <<< 'EOT'
                // include library
                require_once(IPS_GetKernelDir() . DIRECTORY_SEPARATOR . "modules"
                                                . DIRECTORY_SEPARATOR . "IPSDuoFern"
                                                . DIRECTORY_SEPARATOR . "libs"
                                                . DIRECTORY_SEPARATOR . "library.php");

                // define helper function to translate
                function Translate($string)
                {
                    $translations = json_decode(file_get_contents(IPS_GetKernelDir()
                        . DIRECTORY_SEPARATOR . "modules"
                        . DIRECTORY_SEPARATOR . "IPSDuoFern"
                        . DIRECTORY_SEPARATOR . "DuoFernConfigurator"
                        . DIRECTORY_SEPARATOR . "locale.json"), true);
                    $translations = $translations["translations"]["de"];

                    // found translation
                    if (array_key_exists($string, $translations)) {
                        return $translations[$string];
                    }

                    // do not translate
                    return $string;
                }
                
                DUOFERN_GetAllDevicesStatus($id);
                echo Translate("Search for devices...");
EOT;

        // define onClick method for remote Pairing
        $data['actions'][9]['onClick'] = <<< 'EOT'
                // include library
                require_once(IPS_GetKernelDir() . DIRECTORY_SEPARATOR . "modules"
                                                . DIRECTORY_SEPARATOR . "IPSDuoFern"
                                                . DIRECTORY_SEPARATOR . "libs"
                                                . DIRECTORY_SEPARATOR . "library.php");

                // define helper function to translate
                function Translate($string)
                {
                    $translations = json_decode(file_get_contents(IPS_GetKernelDir()
                        . DIRECTORY_SEPARATOR . "modules"
                        . DIRECTORY_SEPARATOR . "IPSDuoFern"
                        . DIRECTORY_SEPARATOR . "DuoFernConfigurator"
                        . DIRECTORY_SEPARATOR . "locale.json"), true);
                    $translations = $translations["translations"]["de"];

                    // found translation
                    if (array_key_exists($string, $translations)) {
                        return $translations[$string];
                    }

                    // do not translate
                    return $string;
                }
                
                // enter duo fern code or select device from list
                if ($deviceList == null && $remotePairDuoFernCode == null) {
                    echo Translate("No DuoFern Code entered and no device selected");
                    return;
                }
                
                if ($remotePairDuoFernCode != null) { // duo fern code entered
                    // check valid duo fern code
                    if (!preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $remotePairDuoFernCode)) {
                        echo Translate("Invalid DuoFern code (Format: XXXXXX with X = 0-9, A-F)");
                        return;
                    }
                    $duoFernCode = str_replace(' ', '', $remotePairDuoFernCode);
                   
                } else if ($deviceList != null) { // device selected from list
                    $duoFernCode = str_replace(' ', '', $deviceList['duoFernCode']);
                }
                
                //  get device type name
                $duoFernDeviceType = DuoFernDeviceType::getDeviceType(substr($duoFernCode, 0, 2));
                        
                // start remote pair
                $result = DUOFERN_DeviceRemotePair($id, $duoFernCode);
                
                if ($result !== false) {
                    echo sprintf(Translate("Device %s paired successful"), ($duoFernDeviceType != false ? 
                            $duoFernDeviceType . " (" . trim(chunk_split($duoFernCode, 2, " ")) . ")" :
                            trim(chunk_split($duoFernCode, 2, " "))));
                } else {
                    echo sprintf(Translate("Device %s not paired"), ($duoFernDeviceType != false ? 
                            $duoFernDeviceType . " (" . trim(chunk_split($duoFernCode, 2, " ")) . ")" :
                            trim(chunk_split($duoFernCode, 2, " "))));
                }    
EOT;

        // define onClick method for pairing mode
        $data['actions'][13]['onClick'] = <<< 'EOT'
                // include library
                require_once(IPS_GetKernelDir() . DIRECTORY_SEPARATOR . "modules"
                                                . DIRECTORY_SEPARATOR . "IPSDuoFern"
                                                . DIRECTORY_SEPARATOR . "libs"
                                                . DIRECTORY_SEPARATOR . "library.php");

                // define helper function to translate
                function Translate($string)
                {
                    $translations = json_decode(file_get_contents(IPS_GetKernelDir()
                        . DIRECTORY_SEPARATOR . "modules"
                        . DIRECTORY_SEPARATOR . "IPSDuoFern"
                        . DIRECTORY_SEPARATOR . "DuoFernConfigurator"
                        . DIRECTORY_SEPARATOR . "locale.json"), true);
                    $translations = $translations["translations"]["de"];

                    // found translation
                    if (array_key_exists($string, $translations)) {
                        return $translations[$string];
                    }

                    // do not translate
                    return $string;
                }
    
                echo Translate("Start pairing mode...") . "\n" . Translate("See messages for more details");
                DUOFERN_StartPairingMode($id, 60);
EOT;

        // define onClick method for unpairing mode
        $data['actions'][14]['onClick'] = <<< 'EOT'
                // include library
                require_once(IPS_GetKernelDir() . DIRECTORY_SEPARATOR . "modules"
                                                . DIRECTORY_SEPARATOR . "IPSDuoFern"
                                                . DIRECTORY_SEPARATOR . "libs"
                                                . DIRECTORY_SEPARATOR . "library.php");

                // define helper function to translate
                function Translate($string)
                {
                    $translations = json_decode(file_get_contents(IPS_GetKernelDir()
                        . DIRECTORY_SEPARATOR . "modules"
                        . DIRECTORY_SEPARATOR . "IPSDuoFern"
                        . DIRECTORY_SEPARATOR . "DuoFernConfigurator"
                        . DIRECTORY_SEPARATOR . "locale.json"), true);
                    $translations = $translations["translations"]["de"];

                    // found translation
                    if (array_key_exists($string, $translations)) {
                        return $translations[$string];
                    }

                    // do not translate
                    return $string;
                }
    
                echo Translate("Start unpairing mode...") . "\n" . Translate("See messages for more details");
                DUOFERN_StartUnpairingMode($id, 60);
EOT;

        return json_encode($data);
    }
}

?>
<?

/**
 * Private functions for this module
 *
 */
trait PrivateFunction
{
    /**
     * Initiates the duo fern connection
     *
     * @param int $retry
     *            count of retries
     * @return boolean true if success, false if fail
     */
    private function Init($retry = 5)
    {
        // set instance status to init pending
        $this->SetStatus(self::IS_INIT_PENDING);

        // get gateway duo fern code
        $gatewayDuoFernCode = $this->ReadPropertyString('duoFernCode');

        // get device duo fern codes
        $deviceDuoFernCodes = $this->DeviceDuoFernCodes;

        for ($i = 0; $i < $retry; $i++) {
            $this->SendDebug("INIT START", $this->ConvertMsgToSend("00000000000000000000000000000000000000000000"), 1);

            // init 1
            $response = $this->SendMsg(DuoFernMessage::DUOFERN_MSG_INIT_1);
            if ($response !== $this->ExpectedResponse(DuoFernMessage::DUOFERN_MSG_INIT_1)) {
                $this->SendDebug("INIT FAIL", $this->ConvertMsgToSend($response), 1);
                continue;
            }

            // init 2
            $response = $this->SendMsg(DuoFernMessage::DUOFERN_MSG_INIT_2);
            if ($response !== $this->ExpectedResponse(DuoFernMessage::DUOFERN_MSG_INIT_2)) {
                $this->SendDebug("INIT FAIL", $this->ConvertMsgToSend($response), 1);
                continue;
            }

            // init serial
            $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_INIT_SERIAL, $gatewayDuoFernCode);
            $response = $this->SendMsg($msg);
            if ($response !== $this->ExpectedResponse($msg)) {
                $this->SendDebug("INIT FAIL", $this->ConvertMsgToSend($response), 1);
                continue;
            }

            // init 3
            $response = $this->SendMsg(DuoFernMessage::DUOFERN_MSG_INIT_3);
            if ($response !== $this->ExpectedResponse(DuoFernMessage::DUOFERN_MSG_INIT_3)) {
                $this->SendDebug("INIT FAIL", $this->ConvertMsgToSend($response), 1);
                continue;
            }

            // init pair table
            $initFailedAtPairTable = false;
            foreach ($deviceDuoFernCodes as $number => $deviceDuoFernCode) {
                $msg = DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_INIT_PAIRTABLE, $deviceDuoFernCode, $number);
                $response = $this->SendMsg($msg);

                // init failed at pair table
                if ($response !== $this->ExpectedResponse($msg)) {
                    $initFailedAtPairTable = true;
                    break;
                }

                // forward to devices
                $this->SendPairTableNumber(utf8_encode($number), $deviceDuoFernCode);
            }

            // init failed at pair table
            if ($initFailedAtPairTable === true) {
                $this->SendDebug("INIT FAIL", $this->ConvertMsgToSend($response), 1);
                continue;
            }

            // init end
            $response = $this->SendMsg(DuoFernMessage::DUOFERN_MSG_INIT_END);
            if ($response !== $this->ExpectedResponse(DuoFernMessage::DUOFERN_MSG_INIT_END)) {
                $this->SendDebug("INIT FAIL", $this->ConvertMsgToSend($response), 1);
                continue;
            }

            // wait 5 ms and send init success debug msg
            IPS_Sleep(5);
            $this->SendDebug("INIT SUCCESS", $this->ConvertMsgToSend("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF"), 1);

            // get status of all devices n times
            $n = 1;
            for ($i = 0; $i < $n; $i++) {
                $this->SendMsg(DuoFernMessage::DUOFERN_MSG_GET_ALL_DEVICES_STATUS);
            }

            // get device status of each device
            /*
            foreach ($deviceDuoFernCodes as $number => $deviceDuoFernCode) {
                $this->SendMsg(DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_GET_DEVICE_STATUS, $deviceDuoFernCode));
            }
            */

            // set instance status to active
            $this->SetStatus(IS_ACTIVE);

            return true;
        }

        // reset pair table numbers at devices
        $this->ResetPairTableNumbersOnDevices($deviceDuoFernCodes);

        // set instance status to initialization failed
        $this->SetStatus(self::IS_INIT_FAILED);

        return false;
    }

    /**
     * Sends a pair table number to device
     * @param $number
     * @param $deviceDuoFernCode
     * @return string|bool
     */
    private function SendPairTableNumber($number, $deviceDuoFernCode)
    {
        $result = parent::SendDataToChildren(json_encode(Array(
            "DataID" => "{244143D3-BABA-44D4-8740-B997B8F09E50}",
            "DuoFernCode" => $deviceDuoFernCode,
            "PairTableNumber" => utf8_encode($number)
        )));

        return $result;
    }

    /**
     * Resets pair table numbers at devices
     * @param $deviceDuoFernCodes
     */
    private function ResetPairTableNumbersOnDevices($deviceDuoFernCodes)
    {
        foreach ($deviceDuoFernCodes as $deviceDuoFernCode) {
            $this->SendPairTableNumber("FF", $deviceDuoFernCode);
        }

        return;
    }

    /**
     * Sends a message to parent io
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return string|boolean response if sent, false if not, NULL if no answer (ACK)
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

        if (!IPS_SemaphoreEnter('DUOFERN_SendMsg', 10000)) {
            $this->SendDebug("FAILED TRANSMIT", $data, 1);
            return false;
        }

        // send to parent io
        $result = $this->SendDataToParent($data);

        // msg not sent
        if ($result === false) {
            IPS_SemaphoreLeave('DUOFERN_SendMsg');
            return false;
        }

        // ACK has no answer
        if (strcmp($msg, DuoFernMessage::DUOFERN_MSG_ACK) === 0) {
            IPS_SemaphoreLeave('DUOFERN_SendMsg');
            return NULL;
        }

        // wait for response
        $response = $this->WaitForResponseOf($msg);

        IPS_SemaphoreLeave('DUOFERN_SendMsg');

        return $response;
    }

    /**
     * Generates a valid random gateway duofern code
     *
     * @return string random valid gateway dufern code
     */
    private function RandomGatewayDuoFernCode()
    {
        return "6F" . strtoupper(bin2hex(openssl_random_pseudo_bytes(2)));
    }

    /**
     * Waits for a given response
     *
     * @param string $exptedResponse
     *            expected response msg
     * @return boolean|string response msg or false on failure
     */
    private function WaitForResponse($expectedResponse)
    {
        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG, $expectedResponse)) {
            return false;
        }

        // try to get response 500 times
        for ($i = 0; $i < 500; $i++) {
            // get data
            $response = $this->WaitForResponseBuffer->Get($expectedResponse);

            // remove msg from buffer (success)
            if ($response !== false) {
                if ($this->SemaphoreEnter('WaitForResponseBuffer')) {
                    $waitForResponseBuffer = $this->WaitForResponseBuffer;
                    $waitForResponseBuffer->Remove($expectedResponse);
                    $this->WaitForResponseBuffer = $waitForResponseBuffer;
                    $this->SemaphoreLeave('WaitForResponseBuffer');
                    // $this->SendDebug ( "RECEIVED RESPONSE", $this->ConvertMsgToSend ( $expectedResponse ), 1 );

                    return $response;
                }
            }

            // wait 10 ms
            IPS_Sleep(10);
        }

        // remove msg from buffer (timeout)
        if ($this->SemaphoreEnter('WaitForResponseBuffer')) {
            $waitForResponseBuffer = $this->WaitForResponseBuffer;
            $waitForResponseBuffer->Remove($expectedResponse);
            $this->WaitForResponseBuffer = $waitForResponseBuffer;
            $this->SemaphoreLeave('WaitForResponseBuffer');
        }

        // send expected response as debug msg
        $this->SendDebug("TIMEOUT WAITFORRESPONSE", $this->ConvertMsgToSend($expectedResponse), 1);

        return false;
    }

    /**
     * Waits for a response of a msg
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return boolean|string response msg or false on failure
     */
    private function WaitForResponseOf($msg)
    {
        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG, $msg)) {
            return false;
        }

        $expectedResponse = $this->ExpectedResponse($msg);

        // invalid expected response
        if ($expectedResponse === false) {
            return false;
        }

        return $this->WaitForResponse($expectedResponse);
    }

    /**
     * Updates parent data, buffers parents instance id and register/unregister msgs
     *
     * @return int parent instance id
     */
    private function UpdateParentData()
    {
        $oldParentId = $this->ParentInstanceID;
        $parentId = IPS_GetInstance($this->InstanceID)['ConnectionID'];

        // no parent change
        if ($parentId == $oldParentId) {
            return $parentId;
        }

        // unregister messages at old parent
        if ($oldParentId > 0) {
            // unregister messages
            $this->UnregisterMessage($oldParentId, IM_CHANGESTATUS);
        }

        // register messages at new parent
        if ($parentId > 0) {
            // register messages
            $this->RegisterMessage($parentId, IM_CHANGESTATUS);
        } else {
            $parentId = 0;
        }

        $this->ParentInstanceID = $parentId;

        return $parentId;
    }

    /**
     * Updates children data, buffers children instance ids and register/unregister msgs
     *
     * @return int[] parent instance id
     */
    private function UpdateChildrenData()
    {
        $oldChildrenIds = $this->ChildrenInstanceIDs;
        $childrenIds = IPS_GetInstanceListByModuleID("{BE62B172-BABA-4EB1-8C4C-507526645ED5}");

        // ignore instances if they are not children
        foreach ($childrenIds as $key => $childId) {
            if (IPS_GetInstance($childId) ['ConnectionID'] != $this->InstanceID) {
                unset($childrenIds[$key]);
            }
        }

        // no children changes
        if ($childrenIds == $oldChildrenIds) {
            return $childrenIds;
        }

        // unregister messages at old children
        foreach (array_diff($oldChildrenIds, $childrenIds) as $oldChildId) {
            // unregister messages
            if ($oldChildId > 0) {
                $this->UnregisterMessage($oldChildId, IM_CHANGESETTINGS);
                $this->UnregisterMessage($oldChildId, FM_DISCONNECT);
            }
        }

        // register messages at new children
        foreach (array_diff($childrenIds, $oldChildrenIds) as $childId) {
            // register messages
            if ($childId > 0) {
                $this->RegisterMessage($childId, IM_CHANGESETTINGS);
                $this->RegisterMessage($childId, FM_DISCONNECT);
            }
        }

        $this->ChildrenInstanceIDs = $childrenIds;

        return $childrenIds;
    }

    /**
     * Forces refresh, init duofern connection
     */
    private function ForceRefresh()
    {
        // update children data and register/unregister msgs
        $this->UpdateChildrenData();

        // update device duo fern codes
        $this->DeviceDuoFernCodes = $this->GetDeviceDuoFernCodes();

        // update parent data and register/unregister msgs
        if ($this->UpdateParentData() != 0 && $this->IsInstanceActive() && $this->IsParentInstanceActive()) {
            // initiates the duo fern connection
            $this->Init();
        } else {
            $this->ResetPairTableNumbersOnDevices($this->DeviceDuoFernCodes);
        }
    }

    /**
     * Gets duo fern codes of all devices
     *
     * @return string[] array with duo fern codes in format /^[0-9A-F]{6}$/
     */
    private function GetDeviceDuoFernCodes()
    {
        $duoFernCodes = array();
        $childrenIds = IPS_GetInstanceListByModuleID("{BE62B172-BABA-4EB1-8C4C-507526645ED5}");

        $i = 0;
        foreach ($childrenIds as $childId) {
            // ignore instance if it is not a child
            if (IPS_GetInstance($childId) ['ConnectionID'] != $this->InstanceID) {
                continue;
            }

            // 100 devices maximum
            if ($i > 100) {
                break;
            }

            // get child duo fern code
            $duoFernCode = IPS_GetProperty($childId, "duoFernCode");

            // add duo fern code to array
            if ($duoFernCode !== false && preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
                $duoFernCodes [sprintf("%02X", $i++)] = $duoFernCode;
            }
        }

        return $duoFernCodes;
    }
}

/**
 * DuoFernWaitForResponseBuffer
 * Buffers expected response msgs while waiting for response
 */
class DuoFernWaitForResponseBuffer
{
    public $items = array();

    /**
     * Adds a msg
     *
     * @param string $msg
     *            msg
     * @return boolean always true
     */
    public function Add($msg)
    {
        $this->items [] = $msg;
        return true;
    }

    /**
     * Removes a msg
     *
     * @param string $msg
     *            msg
     * @return boolean true if removed, false if not
     */
    public function Remove($msg)
    {
        if (($key = array_search($msg, $this->items)) !== false) {
            unset ($this->items [$key]);
            return true;
        }
        return false;
    }

    /**
     * Gets a msg
     *
     * @param string $msg
     *            msg
     * @return string|boolean msg or false if not found
     */
    public function Get($msg)
    {
        if (($key = array_search($msg, $this->items)) !== false) {
            return $this->items [$key];
        }
        return false;
    }

    /**
     * Serializes array items
     *
     * @return string[] array with all items
     */
    public function __sleep()
    {
        return array(
            "items"
        );
    }
}

?>
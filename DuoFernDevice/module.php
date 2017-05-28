<?
require_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "library.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_private.php");
require_once(__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");

/**
 * IPSDuofern - Control Rademacher DuoFern devices with IP-Symcon
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
    }

    /**
     * Applies the changes
     * Will be called when click on "Apply" at the configuration form or after creating the instance
     */
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // require gateway as parent
        $this->RequireParent("{7AB07511-BABA-418B-81C5-88A7C709D318}");

        // property duoFernCode
        $duoFernCode = $this->ReadPropertyString("duoFernCode");
        if (!preg_match(DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
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

        // get msg
        $msg = utf8_decode($data->Buffer);

        // send msg as debug msg
        $this->SendDebug("RECEIVED", $msg, 1);

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
        // discard if no active parent
        if (!$this->IsParentInstanceActive() || $this->GetStatus() == self::IS_INVALID_DUOFERN_CODE) {
            $this->SendDebug("DISCARD TRANSMIT", $Data, 1);
            // set status to device not available
            if ($this->GetStatus() == IS_ACTIVE) {
                $this->SetStatus(self::IS_DEVICE_NOT_AVAILABLE);
            }
            // trigger error
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
            // set status to device not available
            $this->SetStatus(self::IS_DEVICE_NOT_AVAILABLE);

            // trigger error
            trigger_error($this->Translate("Message could not be sent") . PHP_EOL, E_USER_ERROR);

            return false;
        }

        // send msg as debug msg
        if (strcmp($this->ConvertMsgToDisplay($Data), DUOFERN_MSG_ACK) === 0) {
            $this->SendDebug("TRANSMIT ACK", $Data, 1);
        } else {
            $this->SendDebug("TRANSMIT", $Data, 1);
        }

        // set status active
        $this->SetStatus(IS_ACTIVE);

        return $result;
    }

    /**
     * WORKAROUND UNTIL 4.3
     * Translates a given string with locale.json
     * @param $string
     * @return mixed
     */
    private function Translate($string)
    {
        $translations = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "locale.json"), true);
        $translations = $translations["translations"]["de"];

        // found translation
        if (array_key_exists($string, $translations)) {
            return $translations[$string];
        }

        // do not translate
        return $string;
    }
}

?>
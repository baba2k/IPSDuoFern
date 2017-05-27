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

        } else {
            $this->SetStatus(IS_ACTIVE);
        }

        // set receive data filter
        $this->SetReceiveDataFilter("(.*" . $this->ConvertMsgToSend($duoFernCode) . ".*)");
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
    }
}

?>
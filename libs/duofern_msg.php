<?

/**
 * Class DuoFernMessage
 */
class DuoFernMessage
{
    // ACK
    const DUOFERN_MSG_ACK = "81000000000000000000000000000000000000000000";

    // Init
    const DUOFERN_MSG_INIT_1 = "01000000000000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_2 = "0E000000000000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_SERIAL = "0Axxxxxx000100000000000000000000000000000000";
    const DUOFERN_MSG_INIT_3 = "14140000000000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_PAIRTABLE = "03yyxxxxxx0000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_END = "10010000000000000000000000000000000000000000";

    // GetAllDevicesStatus
    const DUOFERN_MSG_GET_ALL_DEVICES_STATUS = "0DFF0F400000000000000000000000000000FFFFFF01";

    /**
     * Generates init serial msg
     *
     * @param string $duoFernCode
     *            duo fern code in format /^6F[0-9A-F]{4}$/
     * @return string|boolean init serial msg or false if invalid duo fern code
     */
    public static function MsgInitSerial($duoFernCode)
    {
        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_GATEWAY_DUOFERN_CODE, $duoFernCode)) {
            return false;
        }

        return preg_replace("/xxxxxx/", $duoFernCode, DuoFernMessage::DUOFERN_MSG_INIT_SERIAL);
    }

    /**
     * Generates init pair table msg
     *
     * @param string $number
     *            number of device in format /^[0-9A-F]{2}$/
     * @param string $duoFernCode
     *            duo fern code in format /^[0-9A-F]{6}$/
     * @return boolean init pairtable msg or false if invalid duo fern code or number
     */
    public static function MsgInitPairtable($number, $duoFernCode)
    {
        // check valid pairtable number
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_PAIRTABLE_NUMBER, $number)) {
            return false;
        }

        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
            return false;
        }

        // replace number and duo fern code
        $msg = preg_replace("/yy/", $number, DuoFernMessage::DUOFERN_MSG_INIT_PAIRTABLE);
        $msg = preg_replace("/xxxxxx/", $duoFernCode, $msg);

        return $msg;
    }

    /**
     * Generates the expected response of a msg
     *
     * @param $msg
     *              message in hex string in format /^[0-9A-F]{44}$/
     * @param $duoFernCode
     *              fern codes in format /^[0-9A-F]{6}$/
     * @return bool|string generated response message
     */
    public static function GenerateResponse($msg, $duoFernCode)
    {
        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG, $msg) || !preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
            return false;
        }

        switch ($msg) {
            case self::MsgInitSerial($duoFernCode) :
                $generatedResponse = "81" . substr($msg, 2, 6) . "0100" . substr($msg, 12);
                break;
            case DuoFernMessage::DUOFERN_MSG_GET_ALL_DEVICES_STATUS :
                $generatedResponse = "81000000" . substr($msg, 8);
                break;
            default :
                $generatedResponse = "81" . substr($msg, 2);
        }
        return $generatedResponse;
    }
}

?>
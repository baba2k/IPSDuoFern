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

    // Device status
    const DUOFERN_MSG_GET_ALL_DEVICES_STATUS = "0DFF0F400000000000000000000000000000FFFFFF01";
    const DUOFERN_MSG_GET_DEVICE_STATUS = "0DFF0F400000000000000000000000000000xxxxxx01";
    const DUOFERN_MSG_STATUS = "0FFF0Fggdd00aaaaaaaaaaaavv00yyzzzzzzxxxxxx01";
    // gg = 23 and 22 device group
    // vv \ 10 = version e.g. vv = 25 => version 2.5
    // dd = 09 or 0F?
    // zzzzzz = sender duo fern code
    // xxxxxx = receiver duo fern code
    // commands with yy = pair table number (or can be every hex number like FF)

    // Pairing
    const DUOFERN_MSG_PAIR_START = "04000000000000000000000000000000000000000000";
    const DUOFERN_MSG_PAIR_STOP = "05000000000000000000000000000000000000000000";
    const DUOFERN_MSG_UNPAIR_START = "07000000000000000000000000000000000000000000";
    const DUOFERN_MSG_UNPAIR_STOP = "08000000000000000000000000000000000000000000";
    const DUOFERN_MSG_REMOTE_PAIR = "0D0006010000000000000000000000000000xxxxxx01";
    const DUOFERN_MSG_REMOTE_PAIR_START = "0D01060100000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_REMOTE_UNPAIR_START = "0D01060200000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_REMOTE_PAIR_UNPAIR_STOP = "0D01060300000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_PAIRED = "0602010000000000000000000000yyxxxxxx00000000";
    const DUOFERN_MSG_UNPAIRED = "0603010000000000000000000000yyxxxxxx00000000";
    const DUOFERN_MSG_ALREADY_UNPAIRED = "0605010000000000000000000000yyxxxxxx00000000";

    // commands
    const DUOFERN_MSG_PING = "0D01071600000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_ON = "0D010E0300000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_OFF = "0D010E0200000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTO = "0D01080600FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_MANUAL = "0D01080600FD0000000000000000yy000000xxxxxx00";

    /**
     * Generates a msg
     * @param string $msg
     *            message in format /^[0-9A-F]{44}$/
     * @param string|bool $duoFernCode
     *            duo fern code in format /^[0-9A-F]{6}$/
     * @param string|bool $number
     *            number of device in format /^[0-9A-F]{2}$/
     * @return bool|string
     */
    public static function GenerateMessage($msg, $duoFernCode = false, $number = false)
    {
        // check valid duo fern code
        if ($duoFernCode !== false && !preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
            return false;
        }

        //  replace duo fern code
        if ($duoFernCode !== false) {
            $msg = preg_replace("/xxxxxx/", $duoFernCode, $msg);
        }

        // replace pair table number
        if ($number !== false) {
            $msg = preg_replace("/yy/", $number, $msg);
        }

        // check valid msg
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG_WILDCHARS, $msg)) {
            return false;
        }

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
        if (!preg_match(DuoFernRegex::DUOFERN_REGEX_MSG, $msg)
            || !preg_match(DuoFernRegex::DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode)) {
            return false;
        }

        switch ($msg) {
            // init serial
            case DuoFernMessage::GenerateMessage(DuoFernMessage::DUOFERN_MSG_INIT_SERIAL, $duoFernCode):
                $generatedResponse = "81" . substr($msg, 2, 6) . "0100" . substr($msg, 12);
                break;
            // device status
            case preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_GET_DEVICE_STATUS, 0, 8) . '.{36}$/', $msg) ? $msg : !$msg:
            case preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_REMOTE_PAIR, 0, 8) . '.{36}$/', $msg) ? $msg : !$msg:
                $generatedResponse = "81000000" . substr($msg, 8);
                break;
            default :
                $generatedResponse = "81" . substr($msg, 2);
        }
        return $generatedResponse;
    }
}

?>
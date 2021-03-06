<?

/**
 * Class DuoFernMessage
 */
class DuoFernMessage
{
    // replacements by reverse engineering
    //
    // gg = device group e.g. 23 and 22
    // vv \ 10 = version e.g. vv = 25 => version 2.5
    // dd = 09 or 0F unknown
    // zzzzzz = sender duo fern code
    // xxxxxx = receiver duo fern code
    // yy = pair table number e.g. hex number like FF
    // cccc = stair case function (0001-FE81). if stair case function is on (cccc >= 8000)
    // then cccc - 8000 = stair case function time else if (cccc < 8000) then cccc = stair case function time
    // ss = state and mode change e.g. devGroup 22: 64 actor on and mode change off, 80 actor off and mode change on + E4 actor on and mode change on

    // ACK
    const DUOFERN_MSG_ACK = "81000000000000000000000000000000000000000000";

    // init
    const DUOFERN_MSG_INIT_1 = "01000000000000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_2 = "0E000000000000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_SERIAL = "0Axxxxxx000100000000000000000000000000000000";
    const DUOFERN_MSG_INIT_3 = "14140000000000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_PAIRTABLE = "03yyxxxxxx0000000000000000000000000000000000";
    const DUOFERN_MSG_INIT_END = "10010000000000000000000000000000000000000000";

    // device status
    const DUOFERN_MSG_GET_ALL_DEVICES_STATUS = "0DFF0F400000000000000000000000000000FFFFFF01";
    const DUOFERN_MSG_GET_DEVICE_STATUS = "0DFF0F400000000000000000000000000000xxxxxx01";
    const DUOFERN_MSG_STATUS = "0FFF0Fggdd00aaaaccccaassvv00yyzzzzzzxxxxxx01";

    // pairing
    const DUOFERN_MSG_PAIR_START = "04000000000000000000000000000000000000000000";
    const DUOFERN_MSG_PAIR_STOP = "05000000000000000000000000000000000000000000";
    const DUOFERN_MSG_UNPAIR_START = "07000000000000000000000000000000000000000000";
    const DUOFERN_MSG_UNPAIR_STOP = "08000000000000000000000000000000000000000000";
    const DUOFERN_MSG_MANUAL_DELETE = "18yyxxxxxx0000000000000000000000000000000000";
    const DUOFERN_MSG_REMOTE_PAIR = "0D0006010000000000000000000000000000xxxxxx01";
    const DUOFERN_MSG_REMOTE_PAIR_START = "0D01060100000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_REMOTE_UNPAIR_START = "0D01060200000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_REMOTE_PAIR_UNPAIR_STOP = "0D01060300000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_PAIRED = "0602010000000000000000000000yyxxxxxx00000000";
    const DUOFERN_MSG_UNPAIRED = "0603010000000000000000000000yyxxxxxx00000000";
    const DUOFERN_MSG_ALREADY_UNPAIRED = "0605010000000000000000000000yyxxxxxx00000000";

    // automation
    const DUOFERN_MSG_MANUALMODE_ON = "0D01080600FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_MANUALMODE_OFF = "0D01080600FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATICMODE_ON = self::DUOFERN_MSG_MANUALMODE_OFF;
    const DUOFERN_MSG_AUTOMATICMODE_OFF = self::DUOFERN_MSG_MANUALMODE_ON;
    const DUOFERN_MSG_AUTOMATIC_TIME_ON = "0D01080400FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_TIME_OFF = "0D01080400FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_DAWN_ON = "0D01080900FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_DAWN_OFF = "0D01080900FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_SUN_ON = "0D01080100FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_SUN_OFF = "0D01080100FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_DUSK_ON = "0D01080500FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_DUSK_OFF = "0D01080500FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_RAIN_ON = "0D01080800FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_RAIN_OFF = "0D01080800FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_WIND_ON = "0D01080700FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_AUTOMATIC_WIND_OFF = "0D01080700FE0000000000000000yy000000xxxxxx00";

    // commands
    const DUOFERN_MSG_RESET_DEVICE = "0D010815CB000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_FULL_RESET_DEVICE = "0D010815CC000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_PING = "0D01071600000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_STATE_ON ="0D010E0300000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_STATE_OFF ="0D010E0200000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_MODE_CHANGE ="0D01070C00000000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_STAIRCASE_FUNCTION_ON ="0D01081400FD0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_STAIRCASE_FUNCTION_OFF ="0D01081400FE0000000000000000yy000000xxxxxx00";
    const DUOFERN_MSG_STAIRCASE_FUNCTION_TIME ="0D0108140000tttt000000000000yy000000xxxxxx00"; // tttt = 0001 (1)decimal to 7E81 (32385)decimal

    /**
     * Generates a msg
     * @param string $msg
     *            message in format /^[0-9A-F]{44}$/
     * @param string|bool $duoFernCode
     *            duo fern code in format /^[0-9A-F]{6}$/
     * @param string|bool $number
     *            number of device in format /^[0-9A-F\\.]{2}$/
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
     *              duo fern code in format /^[0-9A-F]{6}$/
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
            // command 0D 01 *
            case preg_match('/^' . substr(DuoFernMessage::DUOFERN_MSG_PING, 0, 4) . '.{40}$/', $msg) ? $msg : !$msg:
                $generatedResponse = "810003CC" . substr($msg, 8);
                $generatedResponse = substr_replace($generatedResponse, $duoFernCode, 30, 6);
                break;
            default:
                $generatedResponse = "81" . substr($msg, 2);
        }
        return $generatedResponse;
    }
}

?>
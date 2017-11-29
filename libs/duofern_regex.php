<?

/**
 * Class DuoFernRegex
 */
class DuoFernRegex
{
    // valid duo fern code
    const DUOFERN_REGEX_DUOFERN_CODE = "/^[0-9A-F]{6}$/";

    // valid duo fern gateway code
    const DUOFERN_REGEX_GATEWAY_DUOFERN_CODE = "/^6F[0-9A-F]{4}$/";

    // valid duo fern msg
    const DUOFERN_REGEX_MSG = "/^[0-9A-F]{44}$/";

    // valid ack msg
    const DUOFERN_REGEX_ACK = "/^81[0-9A-F]{42}$/";

    // valid duo fern pair table number
    const DUOFERN_REGEX_PAIRTABLE_NUMBER = "/^[0-9A-F]{2}$/";
}

?>
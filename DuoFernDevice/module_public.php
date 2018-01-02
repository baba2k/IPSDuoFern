<?

/**
 * Public functions for this module
 *
 */
trait PublicFunction
{
    /**
     * Sends a message to parent io
     *
     * @param string $msg
     *            message in hex string in format /^[0-9A-F]{44}$/
     * @return string|boolean response if sent, false if not
     */
    public function SendRawMsg(string $msg)
    {
        return $this->SendMsg($msg);
    }

    /**
     * Writes a boolean value to device
     *
     * @param string $ident ident
     * @param bool $value value
     * @return bool true if written, false if not
     */
    public function WriteValueBoolean(string $ident, bool $value)
    {
        if (!is_bool($value)) {
            trigger_error($this->Translate("Value is not boolean"), E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($ident, $value);
    }

    /**
     * Writes a integer value to device
     *
     * @param string $ident ident
     * @param int $value value
     * @return bool true if written, false if not
     */
    public function WriteValueInteger(string $ident, int $value)
    {
        if (!is_integer($value)) {
            trigger_error($this->Translate("Value is not integer"), E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($ident, (int)$value);
    }

    /**
     * Writes a float value to device
     *
     * @param string $ident ident
     * @param float $value value
     * @return bool true if written, false if not
     */
    public function WriteValueFloat(string $ident, float $value)
    {
        if (!is_float($value)) {
            trigger_error($this->Translate("Value is not float"), E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($ident, (float)$value);
    }

    /**
     * Writes a string value to device
     *
     * @param string $ident ident
     * @param string $value value
     * @return bool true if written, false if not
     */
    public function WriteValueString(string $ident, string $value)
    {
        if (!is_string($value)) {
            trigger_error($this->Translate("Value is not string"), E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($ident, (string)$value);
    }
}

?>
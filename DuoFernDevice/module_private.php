<?

/**
 * Private functions for this module
 *
 */
trait PrivateFunction
{
    /**
     * Sends a update children data request to gateway
     * @return string|bool
     */
    private function SendUpdateChildrenData()
    {
        $result = parent::SendDataToParent(json_encode(Array(
            "DataID" => "{D608631B-BABA-4D08-ADB0-5364DD6A2526}",
            "UpdateChildrenData" => utf8_encode("true")
        )));

        return $result;
    }
}

?>
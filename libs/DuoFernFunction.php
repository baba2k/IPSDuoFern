<?
trait DuoFernFunction {
	/**
	 * Converts a message from hex string to a string for sending to
	 *
	 * @param string $hex
	 *        	message in hex string in format /^[0-9A-F]{44}$/
	 * @return string data prepared to send to parent
	 */
	private function ConvertMsg($hex) {
		$string = "";
		for($i = 0; $i < strlen ( $hex ) - 1; $i += 2) {
			$string .= chr ( hexdec ( $hex [$i] . $hex [$i + 1] ) );
		}
		
		return $string;
	}
	
	/**
	 * Sends a message to its parent
	 *
	 * @param string $msg
	 *        	message in hex string in format /^[0-9A-F]{44}$/
	 * @return boolean true if sent, false if not
	 */
	private function SendMsg($msg) {
		// check valid msg
		if (! preg_match ( DUOFERN_REGEX_MSG, $msg )) {
			return false;
		}
		
		// convert data from hex to string
		$data = $this->ConvertMsg ( $msg );
		
		// send to parent io
		$result = $this->SendDataToParent ( $data );
		
		return ($result === false ? false : true);
	}
}
?>
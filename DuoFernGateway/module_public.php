<?
trait DuoFernGatewayPublic {
	/**
	 * Sends a message to its parent
	 *
	 * @param string $msg
	 *        	message in hex string in format /^[0-9A-F]{44}$/
	 * @return boolean true if sent
	 */
	public function SendRawMsg($msg) {
		$this->SendMsg ( $msg );
	}
}
?>
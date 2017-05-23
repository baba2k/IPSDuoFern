<?
/**
 * Public functions for this module
 *
 */
trait PublicFunction {
	/**
	 * Sends a message to its parent
	 *
	 * @param string $msg
	 *        	message in hex string in format /^[0-9A-F]{44}$/
	 * @return string|boolean response if sent, false if not
	 */
	public function SendRawMsg(string $msg) {
		return $this->SendMsg ( $msg );
	}
}
?>
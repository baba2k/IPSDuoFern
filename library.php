<?
require_once (__DIR__ . DIRECTORY_SEPARATOR . "ips.php");

/**
 * Global libraray functions
 */
trait LibraryFunction {
	use IPSHelpFunction;
	use DuoFernMessage;
	
	/**
	 * Converts a message from hex string to a binary string for sending
	 *
	 * @param string $hex
	 *        	message in hex string in format /^[0-9A-F]{44}$/
	 * @return string data prepared to send to parent
	 */
	private function ConvertMsgToSend($hex) {
		$str = "";
		for($i = 0; $i < strlen ( $hex ) - 1; $i += 2) {
			$str .= chr ( hexdec ( $hex [$i] . $hex [$i + 1] ) );
		}
		
		return $str;
	}
	
	/**
	 * Converts a message from binary string to a hex string for display
	 *
	 * @param string $str
	 *        	string data
	 * @return string $hex message in hex string in format /^[0-9A-F]{44}$/
	 */
	private function ConvertMsgToDisplay($str) {
		$hex = "";
		$i = 0;
		do {
			$hex .= sprintf ( "%02X", ord ( $str {$i} ) );
			$i ++;
		} while ( $i < strlen ( $str ) );
		
		return $hex;
	}
	
	/**
	 * Sends a message to parent gateway
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
		$data = $this->ConvertMsgToSend ( $msg );
		
		// send to parent io
		$result = $this->SendDataToParent ( $data );
		
		return ($result === false ? false : true);
	}
}

/**
 * DuoFernMessage
 *
 * @var string
 */
// ACK
define ( "DUOFERN_MSG_ACK", "81000000000000000000000000000000000000000000" );

// Init
define ( "DUOFERN_MSG_INIT_1", "01000000000000000000000000000000000000000000" );
define ( "DUOFERN_MSG_INIT_2", "0E000000000000000000000000000000000000000000" );
define ( "DUOFERN_MSG_INIT_SERIAL", "0Axxxxxx000100000000000000000000000000000000" );
define ( "DUOFERN_MSG_INIT_3", "14140000000000000000000000000000000000000000" );
define ( "DUOFERN_MSG_INIT_PAIRTABLE", "03yyxxxxxx0000000000000000000000000000000000" );
define ( "DUOFERN_MSG_INIT_END", "10010000000000000000000000000000000000000000" );

// Functions for generating msgs dynamic
trait DuoFernMessage {
	/**
	 * Generates init serial msg
	 *
	 * @param string $duoFernCode
	 *        	duo fern code in format /^6F[0-9A-F]{4}$/
	 * @return string|boolean init serial msg or false if invalid duo fern code
	 */
	private function MsgInitSerial($duoFernCode) {
		// check valid msg
		if (! preg_match ( DUOFERN_REGEX_GATEWAY_DUOFERN_CODE, $duoFernCode )) {
			return false;
		}
		
		return preg_replace ( "/xxxxxx/", $duoFernCode, DUOFERN_MSG_INIT_SERIAL );
	}
	
	/**
	 * Generates init pairtable msg
	 *
	 * @param string $number
	 *        	number of device in format /^[0-9A-F]{2}$/
	 * @param string $duoFernCode
	 *        	duo fern code in format /^[0-9A-F]{6}$/
	 * @return boolean|unknown init pairtable msg or false if invalid duo fern code or number
	 */
	private function MsgInitPairtable($number, $duoFernCode) {
		// check valid pairtable number
		if (! preg_match ( DUOFERN_REGEX_PAIRTABLE_NUMBER, $number )) {
			return false;
		}
		
		// check valid msg
		if (! preg_match ( DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode )) {
			return false;
		}
		
		// replace number and duo fern code
		$msg = preg_replace ( "/yy/", $number, DUOFERN_MSG_INIT_PAIRTABLE );
		$msg = preg_replace ( "/xxxxxx/", $duoFernCode, $msg );
		
		return $msg;
	}
}

/**
 * DuoFernRegex
 *
 * @var string
 */
// valid duo fern gateway code
define ( "DUOFERN_REGEX_DUOFERN_CODE", "/^[0-9A-F]{6}$/" );

// valid duo fern gateway code
define ( "DUOFERN_REGEX_GATEWAY_DUOFERN_CODE", "/^6F[0-9A-F]{4}$/" );

// valid duo fern msg
define ( "DUOFERN_REGEX_MSG", "/^[0-9A-F]{44}$/" );

// valid ack msg
define ( "DUOFERN_REGEX_ACK", "/^81[0-9A-F]{42}$/" );

// valid duo fern pairtable number
define ( "DUOFERN_REGEX_PAIRTABLE_NUMBER", "/^[0-9A-F]{2}$/" );
?>
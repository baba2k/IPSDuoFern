<?
require_once (__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");
require_once (__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "DuoFernProtocol.php");
class DuoFernGateway extends IPSModule {
	const STATUS_ERROR_INVALID_DUOFERN_CODE = 201;
	const STATUS_INSTANCE_ACTIVE = 102;
	use DuoFernGatewayPublic;
	use DuoFernFunction;
	public function __construct($InstanceID) {
		parent::__construct ( $InstanceID );
	}
	public function Create() {
		parent::Create ();
		
		// force serial port as parent
		$this->ForceParent ( "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}" );
		
		// register properties
		$this->RegisterPropertyInteger ( "modus", 0 );
		$this->RegisterPropertyString ( "duoFernCode", "6F" . strtoupper ( bin2hex ( openssl_random_pseudo_bytes ( 2 ) ) ) );
		
		// register status variables
		$this->RegisterVariableString ( "DuoFernCode", "DuoFern Code" );
	}
	public function ApplyChanges() {
		parent::ApplyChanges ();
		
		// modus
		switch ($this->ReadPropertyInteger ( "modus" )) {
			case 0 : // force serial port as parent
				$this->ForceParent ( "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}" );
				break;
			case 1 : // force client socket as parent
				$this->ForceParent ( "{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}" );
				break;
		}
		
		// duo fern code
		$duoFernCode = $this->ReadPropertyString ( 'duoFernCode' );
		if (! preg_match ( DUOFERN_REGEX_DUOFERN_CODE, $duoFernCode )) {
			$this->SetStatus ( self::STATUS_ERROR_INVALID_DUOFERN_CODE );
		} else {
			$this->SetStatus ( self::STATUS_INSTANCE_ACTIVE );
		}
		
		// set duo fern code status variable
		$duoFernCodeVarId = $this->GetIDForIdent ( "DuoFernCode" );
		if ($duoFernCode !== GetValueString ( $duoFernCodeVarId )) {
			SetValueString ( $duoFernCodeVarId, $duoFernCode );
		}
	}
	
	/**
	 * Receive data from parent IO
	 *
	 * @param string $JSONString
	 *        	data json encoded
	 * @return boolean always true
	 */
	public function ReceiveData($JSONString) {
		// decode data
		$data = json_decode ( $JSONString );
		
		// get receive buffer
		$receiveBuffer = unserialize ( $this->GetBuffer ( "ReceiveBuffer" ) );
		
		// check last receive timestamp, if last receive time > 1s flush buffer
		$lastReceiveTimestamp = $this->GetBuffer ( "LastReceiveTimestamp" );
		$lastReceiveSec = time () - $lastReceiveTimestamp;
		if ($lastReceiveSec > 1 && $receiveBuffer != "") {
			$this->SendDebug ( "FLUSH BUFFER", $receiveBuffer, 1 );
			$receiveBuffer = "";
		}
		
		// add new data at end of receive buffer
		$receiveBuffer .= utf8_decode ( $data->Buffer );
		
		// if complete msg in receive buffer
		if (strlen ( $receiveBuffer ) >= 22) {
			// get msg from buffer
			$msg = substr ( $receiveBuffer, 0, 22 );
			
			// send msg as debug msg
			$this->SendDebug ( "RECEIVED", $msg, 1 );
			
			// remove msg from receive buffer
			$receiveBuffer = substr ( $receiveBuffer, 22 );
		}
		
		// set new receive buffer
		$this->SetBuffer ( "ReceiveBuffer", serialize ( $receiveBuffer ) );
		
		// set last receive timestamp
		$this->SetBuffer ( "LastReceiveTimestamp", time () );
		
		return true;
	}
	protected function SendDataToParent($Data) {
		// send to parent io
		$result = parent::SendDataToParent ( json_encode ( Array (
				"DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}",
				"Buffer" => utf8_encode ( $Data ) 
		) ) );
		
		// send msg as debug msg
		$this->SendDebug ( "TRANSMIT", $Data, 1 );
		
		return ($result === false ? false : true);
	}
	public function ForwardData($JSONString) {
		
		// decode data
		$data = json_decode ( $JSONString );
		
		// send to parent io
		$result = $this->SendDataToParent ( $data->Buffer );
		
		return $result;
	}
	
	/**
	 * Configuration for parent
	 * Set and Lock baudrate, stopbits, databits and parity for serial port
	 *
	 * @return configuration json string
	 */
	public function GetConfigurationForParent() {
		return "{\"BaudRate\": \"115200\", \"StopBits\": \"1\", \"DataBits\": \"8\", \"Parity\": \"None\"}";
	}
}
?>
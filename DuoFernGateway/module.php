<?
class DuoFernGateway extends IPSModule {
	const STATUS_ERROR_INVALID_DUOFERN_CODE = 201;
	const STATUS_INSTANCE_ACTIVE = 102;
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
		$this->DisableAction ( "DuoFernCode" );
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
		$duoFernRegex = "/^6F([0-9A-F]){4}$/";
		if (! preg_match ( $duoFernRegex, $duoFernCode )) {
			$this->SetStatus ( self::STATUS_ERROR_INVALID_DUOFERN_CODE );
		} else {
			$this->SetStatus ( self::STATUS_INSTANCE_ACTIVE );
		}
		
		// set duo fern code status variable
		if ($duoFernCode !== GetValueString ( $this->GetIDForIdent ( "DuoFernCode" ) )) {
			SetValueString ( $this->GetIDForIdent ( "DuoFernCode" ), $duoFernCode );
		}
	}
	public function HelloWorldGateway() {
		echo "Hello World Gateway!";
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
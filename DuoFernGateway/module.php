<?
require_once (__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "library.php");
require_once (__DIR__ . DIRECTORY_SEPARATOR . "module_private.php");
require_once (__DIR__ . DIRECTORY_SEPARATOR . "module_public.php");

/**
 * IPSDuofern - Control Rademacher DuoFern devices with IP-Symcon
 * Module: DuoFern Gateway
 *
 * @author Sebastian Leicht (baba@baba.tk)
 *        
 */
class DuoFernGateway extends IPSModule {
	/**
	 * Module status error codes
	 *
	 * @var int
	 */
	const IS_INVALID_DUOFERN_CODE = IS_EBASE + 1;
	const IS_INIT_FAILED = IS_EBASE + 2;
	
	/**
	 * Traits
	 * Includes a group of methods
	 */
	use LibraryFunction, PrivateFunction, PublicFunction {
		PrivateFunction::SendMsg insteadof LibraryFunction;
	}
	use MagicGetSetAsBuffer;
	
	/**
	 * Creates module properties
	 * Will be called when creating the instance and on starting IP-Symcon
	 */
	public function Create() {
		parent::Create ();
		
		// register properties
		$this->RegisterPropertyInteger ( "modus", 0 );
		$this->RegisterPropertyString ( "duoFernCode", $this->RandomGatewayDuoFernCode () );
		
		// register status variables
		$this->RegisterVariableString ( "DuoFernCode", "DuoFern Code" );
		
		// create buffers
		$this->ReceiveBuffer = "";
		$this->LastReceiveTimestampBuffer = "";
		$this->WaitForResponseBuffer = new DuoFernWaitForResponseBuffer ();
		$this->ParentInstanceID = "";
		$this->ChildrenInstanceIDs = array ();
	}
	
	/**
	 * Applies the changes
	 * Will be called when click on "Apply" at the configuration form or after creating the instance
	 */
	public function ApplyChanges() {
		// register messages
		$this->RegisterMessage ( 0, IPS_KERNELMESSAGE );
		$this->RegisterMessage ( $this->InstanceID, FM_CONNECT );
		$this->RegisterMessage ( $this->InstanceID, FM_DISCONNECT );
		
		// call parent
		parent::ApplyChanges ();
		
		// return when kernel is not ready
		if (IPS_GetKernelRunlevel () != KR_READY) {
			return;
		}
		
		// property modus
		switch ($this->ReadPropertyInteger ( "modus" )) {
			case 0 : // force serial port as parent
				$this->ForceParent ( "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}" );
				break;
			case 1 : // force client socket as parent
				$this->ForceParent ( "{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}" );
				break;
		}
		
		// property duoFernCode
		$duoFernCode = $this->ReadPropertyString ( "duoFernCode" );
		if (! preg_match ( DUOFERN_REGEX_GATEWAY_DUOFERN_CODE, $duoFernCode )) {
			$this->SetStatus ( self::IS_INVALID_DUOFERN_CODE );
		} else {
			$this->SetStatus ( IS_ACTIVE );
		}
		
		// set duo fern code status variable
		$this->UpdateValueByIdent ( "DuoFernCode", $duoFernCode );
		
		// force refresh
		$this->ForceRefresh ();
	}
	
	/**
	 * Receive data from parent
	 * Will be called when receiving data from parent
	 *
	 * @param string $JSONString
	 *        	data json encoded
	 */
	public function ReceiveData($JSONString) {
		// decode data
		$data = json_decode ( $JSONString );
		
		// get buffers
		$receiveBuffer = $this->ReceiveBuffer;
		$lastReceiveTimestampBuffer = $this->LastReceiveTimestampBuffer;
		
		// check last receive timestamp, if last receive time > 5s flush buffer
		$lastReceiveSec = time () - $lastReceiveTimestampBuffer;
		if ($lastReceiveSec > 5 && $receiveBuffer != "") {
			$this->SendDebug ( "FLUSH RECEIVE BUFFER", $receiveBuffer, 1 );
			$receiveBuffer = "";
		}
		
		// add new data at end of receive buffer
		$receiveBuffer .= utf8_decode ( $data->Buffer );
		
		// if complete msg in receive buffer
		if (strlen ( $receiveBuffer ) >= 22) {
			// get msg from buffer
			$msg = substr ( $receiveBuffer, 0, 22 );
			
			// remove msg from receive buffer
			$receiveBuffer = substr ( $receiveBuffer, 22 );
			
			// ack / response msg
			if (preg_match ( DUOFERN_REGEX_ACK, $this->ConvertMsgToDisplay ( $msg ) )) {
				$waitForResponseBuffer = $this->WaitForResponseBuffer;
				$waitForResponseBuffer->Add ( $this->ConvertMsgToDisplay ( $msg ) );
				$this->WaitForResponseBuffer = $waitForResponseBuffer;
				$this->SendDebug ( "RECEIVED RESPONSE", $msg, 1 );
			} else { // normal msg
				$this->SendDebug ( "RECEIVED", $msg, 1 );
			}
			
			// send ack
			if (strcmp ( $this->ConvertMsgToDisplay ( $msg ), DUOFERN_MSG_ACK ) !== 0) {
				$this->SendMsg ( DUOFERN_MSG_ACK );
			}
		}
		
		// set new last receive timestamp
		$lastReceiveTimestampBuffer = time ();
		
		// set buffers
		$this->ReceiveBuffer = $receiveBuffer;
		$this->LastReceiveTimestampBuffer = $lastReceiveTimestampBuffer;
	}
	
	/**
	 * Forwards data to parent and handles data
	 *
	 * @param string $JSONString        	
	 * @return result data
	 */
	public function ForwardData($JSONString) {
		
		// decode data
		$data = json_decode ( $JSONString );
		
		// send to parent io
		$result = $this->SendDataToParent ( $data->Buffer );
		
		return $result;
	}
	
	/**
	 * Sends data to parent
	 * Will be called from methods ForwardData
	 *
	 * @param string $Data        	
	 * @return result data
	 */
	protected function SendDataToParent($Data) {
		
		// discard if instance inactive or no active parent
		if (! $this->IsInstanceActive () || ! $this->IsParentInstanceActive ()) {
			$this->SendDebug ( "DISCARD TRANSMIT", $Data, 1 );
			return false;
		}
		
		// send to parent io
		$result = parent::SendDataToParent ( json_encode ( Array (
				"DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}",
				"Buffer" => utf8_encode ( $Data ) 
		) ) );
		
		// send msg as debug msg
		if (strcmp ( $this->ConvertMsgToDisplay ( $Data ), DUOFERN_MSG_ACK ) === 0) {
			$this->SendDebug ( "TRANSMIT ACK", $Data, 1 );
		} else {
			$this->SendDebug ( "TRANSMIT", $Data, 1 );
		}
		
		return $result;
	}
	
	/**
	 * Handles registered messages
	 * Will be called when receiving a registered msg
	 *
	 * @param int $TimeStamp        	
	 * @param int $SenderID        	
	 * @param string $Message        	
	 * @param array $Data        	
	 */
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		switch ($Message) {
			case IPS_KERNELMESSAGE :
				if ($Data [0] == KR_READY)
					$this->ApplyChanges ();
				break;
			case FM_CONNECT :
			case FM_DISCONNECT :
				$this->ForceRefresh ();
				break;
			case IM_CHANGESTATUS :
				if (($SenderID == @IPS_GetInstance ( $this->InstanceID ) ['ConnectionID']) and ($Data [0] == IS_ACTIVE)) {
					$this->ForceRefresh ();
				}
				break;
			case IM_CHANGESETTINGS :
				$this->SendDebug ( "MSG IM_CHANGESETTINGS", "Message: " . $Message . ", Data: " . $Data . ", SenderID: ", $SenderID . ", Timestamp: " . $TimeStamp, 0 );
				// $this->ForceRefresh ();
				break;
		}
	}
	
	/**
	 * Configurates the parent io serial port
	 * Set and Lock baudrate, stopbits, databits and parity for serial port
	 *
	 * @return configuration json string
	 */
	public function GetConfigurationForParent() {
		return "{\"BaudRate\": \"115200\", \"StopBits\": \"1\", \"DataBits\": \"8\", \"Parity\": \"None\"}";
	}
}
?>
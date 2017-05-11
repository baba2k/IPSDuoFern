<?
class DuoFernParingTable {
	/**
	 * Paring table as array
	 *
	 * @var array
	 */
	public $pairingTable = array ();
	public function Add(string $duoFernCode) {
		$pairingTable [$duoFernCode] = $duoFernCode;
	}
	
	/**
	 * Serialize
	 *
	 * @return string[]
	 */
	public function __sleep() {
		return array (
				"pairingTable" 
		);
	}
}
class DuoFernCode {
	public $duoFernCode;
	public function __sleep() {
		return array (
				"duoFernCode" 
		);
	}
}
?>
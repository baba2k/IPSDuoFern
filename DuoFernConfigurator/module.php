<?
require_once (__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "libs" . DIRECTORY_SEPARATOR . "DuoFernProtocol.php");
class DuoFernConfigurator extends IPSModule {
	use DuoFernFunction;
	public function __construct($InstanceID) {
		parent::__construct ( $InstanceID );
	}
	public function Create() {
		parent::Create ();
		// require gateway as parent
		$this->ForceParent ( "{7AB07511-BABA-418B-81C5-88A7C709D318}" );
	}
	public function ApplyChanges() {
		parent::ApplyChanges ();
	}
}
?>
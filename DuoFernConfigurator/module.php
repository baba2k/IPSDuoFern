<?
class DuoFernConfigurator extends IPSModule {
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
	public function HelloWorldConfigurator() {
		echo "Hello World Configurator!";
	}
}
?>
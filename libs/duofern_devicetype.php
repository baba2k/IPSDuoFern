<?

/**
 * Class DuoFernDeviceType
 */
class DuoFernDeviceType
{
    private static $duoFernDeviceTypes = [
        "40" => array("name" => "RolloTron Standard", "devGroup" => false),
        "41" => array("name" => "RolloTron Comfort Slave", "devGroup" => false),
        "42" => array("name" => "Rohrmotor-Aktor", "devGroup" => 23),
        "43" => array("name" => "Universalaktor", "devGroup" => false),
        "46" => array("name" => "Steckdosenaktor", "devGroup" => 22),
        "47" => array("name" => "Rohrmotor Steuerung", "devGroup" => false),
        "48" => array("name" => "Dimmaktor", "devGroup" => false),
        "49" => array("name" => "Rohrmotor", "devGroup" => false),
        "4B" => array("name" => "Connect-Aktor", "devGroup" => false),
        "4C" => array("name" => "Troll Basis DuoFern", "devGroup" => 23),
        "4E" => array("name" => "SX5", "devGroup" => false),
        "61" => array("name" => "RolloTron Comfort Master", "devGroup" => false),
        "62" => array("name" => "Super Fake Device", "devGroup" => false),
        "65" => array("name" => "Bewegungsmelder", "devGroup" => false),
        "69" => array("name" => "Umweltsensor", "devGroup" => false),
        "70" => array("name" => "Troll Comfort DuoFern", "devGroup" => false),
        "71" => array("name" => "Troll Comfort DuoFern (Lichtmodus)", "devGroup" => false),
        "73" => array("name" => "Raumthermostat", "devGroup" => false),
        "74" => array("name" => "Wandtaster 6fach 230V", "devGroup" => false),
        "A0" => array("name" => "Handsender (6 Gruppen-48 Geräte)", "devGroup" => false),
        "A1" => array("name" => "Handsender (1 Gruppe-48 Geräte)", "devGroup" => false),
        "A2" => array("name" => "Handsender (6 Gruppen-1 Gerät)", "devGroup" => false),
        "A3" => array("name" => "Handsender (1 Gruppe-1 Gerät)", "devGroup" => false),
        "A4" => array("name" => "Wandtaster", "devGroup" => false),
        "A5" => array("name" => "Sonnensensor", "devGroup" => false),
        "A7" => array("name" => "Funksender UP", "devGroup" => false),
        "A8" => array("name" => "HomeTimer", "devGroup" => false),
        "AA" => array("name" => "Markisenwaechter", "devGroup" => false),
        "AB" => array("name" => "Rauchmelder", "devGroup" => false),
        "AD" => array("name" => "Wandtaster 6fach BAT", "devGroup" => false)
    ];

    public static function getDeviceType($deviceTypeCode)
    {
        $deviceTypeCode = substr($deviceTypeCode, 0, 2);
        return isset(self::$duoFernDeviceTypes[$deviceTypeCode]) ? self::$duoFernDeviceTypes[$deviceTypeCode]['name'] : null;
    }

    public static function getDeviceGroup($deviceTypeCode)
    {
        $deviceTypeCode = substr($deviceTypeCode, 0, 2);
        return isset(self::$duoFernDeviceTypes[$deviceTypeCode]) ? self::$duoFernDeviceTypes[$deviceTypeCode]['devGroup'] : null;
    }
}

?>
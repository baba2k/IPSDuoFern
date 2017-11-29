<?

/**
 * Class DuoFernDeviceType
 */
class DuoFernDeviceType
{
    private static $duoFernDeviceTypes = [
        "40" => "RolloTron Standard",
        "41" => "RolloTron Comfort Slave",
        "42" => "Rohrmotor-Aktor",
        "43" => "Universalaktor",
        "46" => "Steckdosenaktor",
        "47" => "Rohrmotor Steuerung",
        "48" => "Dimmaktor",
        "49" => "Rohrmotor",
        "4B" => "Connect-Aktor",
        "4C" => "Troll Basis DuoFern",
        "4E" => "SX5",
        "61" => "RolloTron Comfort Master",
        "65" => "Bewegungsmelder",
        "69" => "Umweltsensor",
        "70" => "Troll Comfort DuoFern",
        "71" => "Troll Comfort DuoFern (Lichtmodus)",
        "73" => "Raumthermostat",
        "74" => "Wandtaster 6fach 230V",
        "A0" => "Handsender (6 Gruppen-48 Geräte)",
        "A1" => "Handsender (1 Gruppe-48 Geräte)",
        "A2" => "Handsender (6 Gruppen-1 Gerät)",
        "A3" => "Handsender (1 Gruppe-1 Gerät)",
        "A4" => "Wandtaster",
        "A5" => "Sonnensensor",
        "A7" => "Funksender UP",
        "A8" => "HomeTimer",
        "AA" => "Markisenwaechter",
        "AB" => "Rauchmelder",
        "AD" => "Wandtaster 6fach BAT",
    ];

    public static function getDeviceType($deviceTypeCode)
    {
        return isset(self::$duoFernDeviceTypes[$deviceTypeCode]) ? self::$duoFernDeviceTypes[$deviceTypeCode] : null;
    }
}

?>
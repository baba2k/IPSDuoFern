<?

/**
 * IP-Symcon help function
 */
trait IPSHelpFunction
{
    /**
     * Gets a value by ident
     *
     * @param string $ident
     * @return mixed value
     */
    private function GetValueByIdent($ident)
    {
        return GetValue($this->GetIDForIdent($ident));
    }

    /**
     * Sets a value by ident
     *
     * @param string $ident
     * @param mixed $value
     * @return boolean true if success, false if not
     */
    private function SetValueByIdent($ident, $value)
    {
        return SetValue($this->GetIDForIdent($ident), $value);
    }

    /**
     * Updates a value by ident
     *
     * @param string $ident
     * @param mixed $value
     * @return boolean true if updated, false if not
     */
    private function UpdateValueByIdent($ident, $value)
    {
        if ($value !== $this->GetValueByIdent($ident)) {
            return $this->SetValueByIdent($ident, $value);
        }

        return false;
    }

    /**
     * Gets instance status
     *
     * @param int $status
     */
    private function GetStatus()
    {
        return IPS_GetInstance($this->InstanceID) ['InstanceStatus'];
    }

    /**
     * Sets instance status
     *
     * @param int $status
     */
    protected function SetStatus($status)
    {
        if ($status != $this->GetStatus())
            parent::SetStatus($status);
    }

    /**
     * Checks if instance is ative
     *
     * @return boolean true if yes, false if not
     */
    private function IsInstanceActive()
    {
        return IPS_GetInstance($this->InstanceID) ['InstanceStatus'] == IPSStatus::IS_ACTIVE ? true : false;
    }

    /**
     * Checks if parent instance is active
     *
     * @return boolean true if yes, false if not
     */
    private function IsParentInstanceActive()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance ['ConnectionID'] > 0) {
            $parent = IPS_GetInstance($instance ['ConnectionID']);
            if ($parent ['InstanceStatus'] == IPSStatus::IS_ACTIVE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enters a semaphore
     *
     * @param string $name
     *            ident of semaphore
     * @return boolean true if entered semaphore, false if not
     */
    private function SemaphoreEnter($name)
    {
        for ($i = 0; $i < 500; $i++) {
            if (IPS_SemaphoreEnter(( string )$name . "_" . ( string )$this->InstanceID, 1)) {
                return true;
            } else {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    /**
     * Leaves a semaphore
     *
     * @param string $ident
     *            ident of semaphore
     * @return boolean true if left semaphore, false if not
     */
    private function SemaphoreLeave($name)
    {
        return IPS_SemaphoreLeave(( string )$name . "_" . ( string )$this->InstanceID);
    }
}

/**
 * Use magic __get and __set methods for reading and writing to a buffer when acces inaccessible properties
 */
trait MagicGetSetAsBuffer
{
    /**
     * Gets a buffer
     * Will be called when reading data from inaccessible properties
     *
     * @param string $name
     *            buffer name
     * @return string buffer value
     */
    public function __get($name)
    {
        // $this->SendDebug ( 'GET_' . $name, unserialize ( $this->GetBuffer ( $name ) ), 0 );
        return unserialize($this->GetBuffer($name));
    }

    /**
     * Sets a buffer
     * Will be called when writing data to inaccessible properties
     *
     * @param string $name
     *            buffer name
     * @param mixed $value
     *            value to write in buffer
     */
    public function __set($name, $value)
    {
        $this->SetBuffer($name, serialize($value));
        // $this->SendDebug ( 'SET_' . $name, serialize ( $value ), 0 );
    }
}

/**
 * Class IPSMessage
 * https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/nachrichten/
 */
class IPSMessage
{
    const IPS_BASE = 10000; // Wertebasis fuer Kernel
    const IPS_MODULBASE = 20000; // Wertebasis fuer Module

    // Service Nachrichten
    const IPS_KERNELSTARTED = self::IPS_BASE + 1;
    const IPS_KERNELSHUTDOWN = self::IPS_BASE + 2;

    // Kernel Manager
    const IPS_KERNELMESSAGE = self::IPS_BASE + 100;
    const KR_CREATE = self::IPS_KERNELMESSAGE + 1; // Kernel wurde erstellt
    const KR_INIT = self::IPS_KERNELMESSAGE + 2; // Kernel Komponenten werden initialisiert, Module geladen und Settings eingelesen
    const KR_READY = self::IPS_KERNELMESSAGE + 3; // Kernel ist bereit und laeuft
    const KR_UNINIT = self::IPS_KERNELMESSAGE + 4; // "Shutdown"-Befehl erhalten, finalisiere alles geladene
    const KR_SHUTDOWN = self::IPS_KERNELMESSAGE + 5; // Finalisierung abgeschlossen, entferne Kernel

    // Meldungen Manager
    const IPS_LOGMESSAGE = self::IPS_BASE + 200;
    const KL_MESSAGE = self::IPS_LOGMESSAGE + 1; // Normale Nachricht
    const KL_SUCCESS = self::IPS_LOGMESSAGE + 2; // Erfolg
    const KL_NOTIFY = self::IPS_LOGMESSAGE + 3; // Aenderungsbenachrichtung
    const KL_WARNING = self::IPS_LOGMESSAGE + 4; // Warnung
    const KL_ERROR = self::IPS_LOGMESSAGE + 5; // Fehlermeldung
    const KL_DEBUG = self::IPS_LOGMESSAGE + 6; // Debug Information
    const KL_CUSTOM = self::IPS_LOGMESSAGE + 7; // Sonstige Nachrichten

    // Modul Manager
    const IPS_MODULEMESSAGE = self::IPS_BASE + 300;
    const ML_LOAD = self::IPS_MODULEMESSAGE + 1; // Modul geladen
    const ML_UNLOAD = self::IPS_MODULEMESSAGE + 2; // Modul entladen

    // Objekt Manager
    const IPS_OBJECTMESSAGE = self::IPS_BASE + 400;
    const OM_REGISTER = self::IPS_OBJECTMESSAGE + 1; // Objekt erstellt
    const OM_UNREGISTER = self::IPS_OBJECTMESSAGE + 2; // Objekt entfernt
    const OM_CHANGEPARENT = self::IPS_OBJECTMESSAGE + 3; // Uebergeordnetes Objekt hat sich geaendert
    const OM_CHANGENAME = self::IPS_OBJECTMESSAGE + 4; // Name hat sich geaendert
    const OM_CHANGEINFO = self::IPS_OBJECTMESSAGE + 5; // Info hat sich geaendert
    const OM_CHANGETYPE = self::IPS_OBJECTMESSAGE + 6; // Typ hat sich geaendert
    const OM_CHANGESUMMARY = self::IPS_OBJECTMESSAGE + 7; // Kurzinfo hat sich geaendert
    const OM_CHANGEPOSITION = self::IPS_OBJECTMESSAGE + 8; // Position hat sich geaendert
    const OM_CHANGEREADONLY = self::IPS_OBJECTMESSAGE + 9; // "Nur-Lesen"-Status hat sich geaendert
    const OM_CHANGEHIDDEN = self::IPS_OBJECTMESSAGE + 10; // Sichtbarkeit hat sich geaendert
    const OM_CHANGEICON = self::IPS_OBJECTMESSAGE + 11; // Icon hat sich geaendert
    const OM_CHILDADDED = self::IPS_OBJECTMESSAGE + 12; // Untergeordnetes Objekt hinzugefuegt
    const OM_CHILDREMOVED = self::IPS_OBJECTMESSAGE + 13; // Untergeordnetes Objekt entfernt
    const OM_CHANGEIDENT = self::IPS_OBJECTMESSAGE + 14; // Ident hat sich geaendert
    const OM_CHANGEDISABLED = self::IPS_OBJECTMESSAGE + 15; // Ident hat sich geaendert

    // Instanz Manager
    const IPS_INSTANCEMESSAGE = self::IPS_BASE + 500;
    const IM_CREATE = self::IPS_INSTANCEMESSAGE + 1; // Instanz erstellt
    const IM_DELETE = self::IPS_INSTANCEMESSAGE + 2; // Instanz entfernt
    const IM_CONNECT = self::IPS_INSTANCEMESSAGE + 3; // Instanzinterface verfuegbar
    const IM_DISCONNECT = self::IPS_INSTANCEMESSAGE + 4; // Instanzinterface nicht mehr verfuegbar
    const IM_CHANGESTATUS = self::IPS_INSTANCEMESSAGE + 5; // Status hat sich geaendert
    const IM_CHANGESETTINGS = self::IPS_INSTANCEMESSAGE + 6; // Einstellungen haben sich geaendert

    // Such Manager
    const IPS_SEARCHMESSAGE = self::IPS_BASE + 510;
    const IM_SEARCHSTART = self::IPS_SEARCHMESSAGE + 1; // Suche wurde gestartet
    const IM_SEARCHSTOP = self::IPS_SEARCHMESSAGE + 1; // Suche wurde gestoppt
    const IM_SEARCHUPDATE = self::IPS_SEARCHMESSAGE + 1; // Suche hat neue Ergebnisse

    // Variablen Manager
    const IPS_VARIABLEMESSAGE = self::IPS_BASE + 600;
    const VM_CREATE = self::IPS_VARIABLEMESSAGE + 1; // Variable wurde erstellt
    const VM_DELETE = self::IPS_VARIABLEMESSAGE + 2; // Variable wurde entfernt
    const VM_UPDATE = self::IPS_VARIABLEMESSAGE + 3; // Variable wurde aktualisiert
    const VM_CHANGEPROFILENAME = self::IPS_VARIABLEMESSAGE + 4; // Variablenprofilname wurde geaendert
    const VM_CHANGEPROFILEACTION = self::IPS_VARIABLEMESSAGE + 5; // Variablenprofilaktion wurde geaendert

    // Script Manager
    const IPS_SCRIPTMESSAGE = self::IPS_BASE + 700;
    const SM_CREATE = self::IPS_SCRIPTMESSAGE + 1; // Skript wurde erstellt
    const SM_DELETE = self::IPS_SCRIPTMESSAGE + 2; // Skript wurde entfernt
    const SM_CHANGEFILE = self::IPS_SCRIPTMESSAGE + 3; // Skript wurde Datei angehangen
    const SM_BROKEN = self::IPS_SCRIPTMESSAGE + 4; // Skript Fehlerstatus hat sich geaendert

    // Event Manager
    const IPS_EVENTMESSAGE = self::IPS_BASE + 800; // Event Scripter Message
    const EM_CREATE = self::IPS_EVENTMESSAGE + 1; // Ereignis wurde erstellt
    const EM_DELETE = self::IPS_EVENTMESSAGE + 2; // Ereignis wurde entfernt
    const EM_UPDATE = self::IPS_EVENTMESSAGE + 3; // Ereignis wurde aktualisiert
    const EM_CHANGEACTIVE = self::IPS_EVENTMESSAGE + 4; // Ereignisaktivierung hat sich geaendert
    const EM_CHANGELIMIT = self::IPS_EVENTMESSAGE + 5; // Ereignisaufruflimit hat sich geaendert
    const EM_CHANGESCRIPT = self::IPS_EVENTMESSAGE + 6; // Ereignisskriptinhalt hat sich geaendert
    const EM_CHANGETRIGGER = self::IPS_EVENTMESSAGE + 7; // Ereignisausloeser hat sich geaendert
    const EM_CHANGETRIGGERVALUE = self::IPS_EVENTMESSAGE + 8; // Ereignisgrenzwert hat sich geaendert
    const EM_CHANGETRIGGEREXECUTION = self::IPS_EVENTMESSAGE + 9; // Ereignisgrenzwertausloesung hat sich geaendert
    const EM_CHANGECYCLIC = self::IPS_EVENTMESSAGE + 10; // Zyklisches Ereignis hat sich geaendert
    const EM_CHANGECYCLICDATEFROM = self::IPS_EVENTMESSAGE + 11; // Startdatum hat sich geaendert
    const EM_CHANGECYCLICDATETO = self::IPS_EVENTMESSAGE + 12; // Enddatum hat sich geaendert
    const EM_CHANGECYCLICTIMEFROM = self::IPS_EVENTMESSAGE + 13; // Startzeit hat sich geaendert
    const EM_CHANGECYCLICTIMETO = self::IPS_EVENTMESSAGE + 14; // Endzeit hat sich geaendert
    const EM_ADDSCHEDULEACTION = self::IPS_EVENTMESSAGE + 15; // Eintrag in der Aktionstabelle des Wochenplans wurde hinzugefuegt
    const EM_REMOVESCHEDULEACTION = self::IPS_EVENTMESSAGE + 16; // Eintrag in der Aktionstabelle des Wochenplans wurde entfernt
    const EM_CHANGESCHEDULEACTION = self::IPS_EVENTMESSAGE + 17; // Eintrag in der Aktionstabelle des Wochenplans hat sich geaendert
    const EM_ADDSCHEDULEGROUP = self::IPS_EVENTMESSAGE + 18; // Gruppierung der Wochenplantage wurde hinzugefuegt
    const EM_REMOVESCHEDULEGROUP = self::IPS_EVENTMESSAGE + 19; // Gruppierung der Wochenplantage wurde entfernt
    const EM_CHANGESCHEDULEGROUP = self::IPS_EVENTMESSAGE + 20; // Gruppierung der Wochenplantage hat sich geaendert
    const EM_ADDSCHEDULEGROUPPOINT = self::IPS_EVENTMESSAGE + 21; // Schaltpunkt einer Gruppierung wurde hinzugefuegt
    const EM_REMOVESCHEDULEGROUPPOINT = self::IPS_EVENTMESSAGE + 22; // Schaltpunkt einer Gruppierung wurde entfernt
    const EM_CHANGESCHEDULEGROUPPOINT = self::IPS_EVENTMESSAGE + 23; // Schaltpunkt einer Gruppierung hat sich geaendert

    // Medien Manager
    const IPS_MEDIAMESSAGE = self::IPS_BASE + 900;
    const MM_CREATE = self::IPS_MEDIAMESSAGE + 1; // Medienobjekt wurde erstellt
    const MM_DELETE = self::IPS_MEDIAMESSAGE + 2; // Medienobjekt wurde entfernt
    const MM_CHANGEFILE = self::IPS_MEDIAMESSAGE + 3; // Datei des Medienobjekts wurde geaendert
    const MM_AVAILABLE = self::IPS_MEDIAMESSAGE + 4; // Verfuegbarkeit des Medienobjekts hat sich geaendert
    const MM_UPDATE = self::IPS_MEDIAMESSAGE + 5; // Medienobjekt wurde aktualisiert
    const MM_CHANGECACHED = self::IPS_MEDIAMESSAGE + 6; // Cacheoption vom Medienobjekt hat sich geaendert

    // Link Manager
    const IPS_LINKMESSAGE = self::IPS_BASE + 1000;
    const LM_CREATE = self::IPS_LINKMESSAGE + 1; // Link wurde erstellt
    const LM_DELETE = self::IPS_LINKMESSAGE + 2; // Link wurde entfernt
    const LM_CHANGETARGET = self::IPS_LINKMESSAGE + 3; // Ziel des Links hat sich geaendert

    // Flow Manager
    const IPS_DATAMESSAGE = self::IPS_BASE + 1100;
    const FM_CONNECT = self::IPS_DATAMESSAGE + 1; // Instanz wurde verbunden
    const FM_DISCONNECT = self::IPS_DATAMESSAGE + 2; // Instanz wurde getrennt

    // Script Engine
    const IPS_ENGINEMESSAGE = self::IPS_BASE + 1200;
    const SE_UPDATE = self::IPS_ENGINEMESSAGE + 1; // Scriptengine wurde neu geladen
    const SE_EXECUTE = self::IPS_ENGINEMESSAGE + 2; // Script wurde ausgefuehrt
    const SE_RUNNING = self::IPS_ENGINEMESSAGE + 3; // Script wird ausgefuehrt

    // Profile Pool
    const IPS_PROFILEMESSAGE = self::IPS_BASE + 1300;
    const PM_CREATE = self::IPS_PROFILEMESSAGE + 1; // Profil wurde erstellt
    const PM_DELETE = self::IPS_PROFILEMESSAGE + 2; // Profil wurde entfernt
    const PM_CHANGETEXT = self::IPS_PROFILEMESSAGE + 3; // Profilprefix/Profilsuffix hat sich geaendert
    const PM_CHANGEVALUES = self::IPS_PROFILEMESSAGE + 4; // Profilwerte haben sich geaendert
    const PM_CHANGEDIGITS = self::IPS_PROFILEMESSAGE + 5; // Profilnachkommastellen haben sich geaendert
    const PM_CHANGEICON = self::IPS_PROFILEMESSAGE + 6; // Profilicon hat sich geaendert
    const PM_ASSOCIATIONADDED = self::IPS_PROFILEMESSAGE + 7; // Profilassoziation wurde hinzugefuegt
    const PM_ASSOCIATIONREMOVED = self::IPS_PROFILEMESSAGE + 8; // Profilassoziation wurde entfernt
    const PM_ASSOCIATIONCHANGED = self::IPS_PROFILEMESSAGE + 9; // Profilassoziation hat sich geaendert

    // Timer Pool
    const IPS_TIMERMESSAGE = self::IPS_BASE + 1400;
    const TM_REGISTER = self::IPS_TIMERMESSAGE + 1; // Timer wurde erstellt
    const TM_UNREGISTER = self::IPS_TIMERMESSAGE + 2; // Timer wurde entfernt
    const TM_SETINTERVAL = self::IPS_TIMERMESSAGE + 3; // Timer Interval hat sich geaendert
    const TM_UPDATE = self::IPS_TIMERMESSAGE + 4; // Timer Fortschritt hat sich geaendert
    const TM_RUNNING = self::IPS_TIMERMESSAGE + 5; // Timer Nachricht
}

/**
 * Class IPSStatus
 * https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/konfigurationsformulare/statusmeldung/
 */
class IPSStatus
{
    // Normal-Status
    const IS_SBASE = 100;
    const IS_CREATING = self::IS_SBASE + 1; // Instanz wird erstellt
    const IS_ACTIVE = self::IS_SBASE + 2; // Instanz ist aktiv
    const IS_DELETING = self::IS_SBASE + 3; // Instanz wird geloescht
    const IS_INACTIVE = self::IS_SBASE + 4; // Instanz ist inaktiv

    // Error-Status
    const IS_EBASE = 200;
}

?>
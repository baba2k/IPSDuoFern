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
 * IP-Symcon message
 * https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/nachrichten/
 */
if (@constant('IPS_BASE') == null) {
    define('IPS_BASE', 10000); // Wertebasis fuer Kernel
    define('IPS_MODULBASE', 20000); // Wertebasis fuer Module

    // Service Nachrichten
    define('IPS_KERNELSTARTED', IPS_BASE + 1);
    define('IPS_KERNELSHUTDOWN', IPS_BASE + 2);

    // Kernel Manager
    define('IPS_KERNELMESSAGE', IPS_BASE + 100);
    define('KR_CREATE', IPS_KERNELMESSAGE + 1); // Kernel wurde erstellt
    define('KR_INIT', IPS_KERNELMESSAGE + 2); // Kernel Komponenten werden initialisiert, Module geladen und Settings eingelesen
    define('KR_READY', IPS_KERNELMESSAGE + 3); // Kernel ist bereit und laeuft
    define('KR_UNINIT', IPS_KERNELMESSAGE + 4); // "Shutdown"-Befehl erhalten, finalisiere alles geladene
    define('KR_SHUTDOWN', IPS_KERNELMESSAGE + 5); // Finalisierung abgeschlossen, entferne Kernel

    // Meldungen Manager
    define('IPS_LOGMESSAGE', IPS_BASE + 200);
    define('KL_MESSAGE', IPS_LOGMESSAGE + 1); // Normale Nachricht
    define('KL_SUCCESS', IPS_LOGMESSAGE + 2); // Erfolg
    define('KL_NOTIFY', IPS_LOGMESSAGE + 3); // Aenderungsbenachrichtung
    define('KL_WARNING', IPS_LOGMESSAGE + 4); // Warnung
    define('KL_ERROR', IPS_LOGMESSAGE + 5); // Fehlermeldung
    define('KL_DEBUG', IPS_LOGMESSAGE + 6); // Debug Information
    define('KL_CUSTOM', IPS_LOGMESSAGE + 7); // Sonstige Nachrichten

    // Modul Manager
    define('IPS_MODULEMESSAGE', IPS_BASE + 300);
    define('ML_LOAD', IPS_MODULEMESSAGE + 1); // Modul geladen
    define('ML_UNLOAD', IPS_MODULEMESSAGE + 2); // Modul entladen

    // Objekt Manager
    define('IPS_OBJECTMESSAGE', IPS_BASE + 400);
    define('OM_REGISTER', IPS_OBJECTMESSAGE + 1); // Objekt erstellt
    define('OM_UNREGISTER', IPS_OBJECTMESSAGE + 2); // Objekt entfernt
    define('OM_CHANGEPARENT', IPS_OBJECTMESSAGE + 3); // Uebergeordnetes Objekt hat sich geaendert
    define('OM_CHANGENAME', IPS_OBJECTMESSAGE + 4); // Name hat sich geaendert
    define('OM_CHANGEINFO', IPS_OBJECTMESSAGE + 5); // Info hat sich geaendert
    define('OM_CHANGETYPE', IPS_OBJECTMESSAGE + 6); // Typ hat sich geaendert
    define('OM_CHANGESUMMARY', IPS_OBJECTMESSAGE + 7); // Kurzinfo hat sich geaendert
    define('OM_CHANGEPOSITION', IPS_OBJECTMESSAGE + 8); // Position hat sich geaendert
    define('OM_CHANGEREADONLY', IPS_OBJECTMESSAGE + 9); // "Nur-Lesen"-Status hat sich geaendert
    define('OM_CHANGEHIDDEN', IPS_OBJECTMESSAGE + 10); // Sichtbarkeit hat sich geaendert
    define('OM_CHANGEICON', IPS_OBJECTMESSAGE + 11); // Icon hat sich geaendert
    define('OM_CHILDADDED', IPS_OBJECTMESSAGE + 12); // Untergeordnetes Objekt hinzugefuegt
    define('OM_CHILDREMOVED', IPS_OBJECTMESSAGE + 13); // Untergeordnetes Objekt entfernt
    define('OM_CHANGEIDENT', IPS_OBJECTMESSAGE + 14); // Ident hat sich geaendert
    define('OM_CHANGEDISABLED', IPS_OBJECTMESSAGE + 15); // Ident hat sich geaendert

    // Instanz Manager
    define('IPS_INSTANCEMESSAGE', IPS_BASE + 500);
    define('IM_CREATE', IPS_INSTANCEMESSAGE + 1); // Instanz erstellt
    define('IM_DELETE', IPS_INSTANCEMESSAGE + 2); // Instanz entfernt
    define('IM_CONNECT', IPS_INSTANCEMESSAGE + 3); // Instanzinterface verfuegbar
    define('IM_DISCONNECT', IPS_INSTANCEMESSAGE + 4); // Instanzinterface nicht mehr verfuegbar
    define('IM_CHANGESTATUS', IPS_INSTANCEMESSAGE + 5); // Status hat sich geaendert
    define('IM_CHANGESETTINGS', IPS_INSTANCEMESSAGE + 6); // Einstellungen haben sich geaendert

    // Such Manager
    define('IPS_SEARCHMESSAGE', IPS_BASE + 510);
    define('IM_SEARCHSTART', IPS_SEARCHMESSAGE + 1); // Suche wurde gestartet
    define('IM_SEARCHSTOP', IPS_SEARCHMESSAGE + 1); // Suche wurde gestoppt
    define('IM_SEARCHUPDATE', IPS_SEARCHMESSAGE + 1); // Suche hat neue Ergebnisse

    // Variablen Manager
    define('IPS_VARIABLEMESSAGE', IPS_BASE + 600);
    define('VM_CREATE', IPS_VARIABLEMESSAGE + 1); // Variable wurde erstellt
    define('VM_DELETE', IPS_VARIABLEMESSAGE + 2); // Variable wurde entfernt
    define('VM_UPDATE', IPS_VARIABLEMESSAGE + 3); // Variable wurde aktualisiert
    define('VM_CHANGEPROFILENAME', IPS_VARIABLEMESSAGE + 4); // Variablenprofilname wurde geaendert
    define('VM_CHANGEPROFILEACTION', IPS_VARIABLEMESSAGE + 5); // Variablenprofilaktion wurde geaendert

    // Script Manager
    define('IPS_SCRIPTMESSAGE', IPS_BASE + 700);
    define('SM_CREATE', IPS_SCRIPTMESSAGE + 1); // Skript wurde erstellt
    define('SM_DELETE', IPS_SCRIPTMESSAGE + 2); // Skript wurde entfernt
    define('SM_CHANGEFILE', IPS_SCRIPTMESSAGE + 3); // Skript wurde Datei angehangen
    define('SM_BROKEN', IPS_SCRIPTMESSAGE + 4); // Skript Fehlerstatus hat sich geaendert

    // Event Manager
    define('IPS_EVENTMESSAGE', IPS_BASE + 800); // Event Scripter Message
    define('EM_CREATE', IPS_EVENTMESSAGE + 1); // Ereignis wurde erstellt
    define('EM_DELETE', IPS_EVENTMESSAGE + 2); // Ereignis wurde entfernt
    define('EM_UPDATE', IPS_EVENTMESSAGE + 3); // Ereignis wurde aktualisiert
    define('EM_CHANGEACTIVE', IPS_EVENTMESSAGE + 4); // Ereignisaktivierung hat sich geaendert
    define('EM_CHANGELIMIT', IPS_EVENTMESSAGE + 5); // Ereignisaufruflimit hat sich geaendert
    define('EM_CHANGESCRIPT', IPS_EVENTMESSAGE + 6); // Ereignisskriptinhalt hat sich geaendert
    define('EM_CHANGETRIGGER', IPS_EVENTMESSAGE + 7); // Ereignisausloeser hat sich geaendert
    define('EM_CHANGETRIGGERVALUE', IPS_EVENTMESSAGE + 8); // Ereignisgrenzwert hat sich geaendert
    define('EM_CHANGETRIGGEREXECUTION', IPS_EVENTMESSAGE + 9); // Ereignisgrenzwertausloesung hat sich geaendert
    define('EM_CHANGECYCLIC', IPS_EVENTMESSAGE + 10); // Zyklisches Ereignis hat sich geaendert
    define('EM_CHANGECYCLICDATEFROM', IPS_EVENTMESSAGE + 11); // Startdatum hat sich geaendert
    define('EM_CHANGECYCLICDATETO', IPS_EVENTMESSAGE + 12); // Enddatum hat sich geaendert
    define('EM_CHANGECYCLICTIMEFROM', IPS_EVENTMESSAGE + 13); // Startzeit hat sich geaendert
    define('EM_CHANGECYCLICTIMETO', IPS_EVENTMESSAGE + 14); // Endzeit hat sich geaendert
    define('EM_ADDSCHEDULEACTION', IPS_EVENTMESSAGE + 15); // Eintrag in der Aktionstabelle des Wochenplans wurde hinzugefuegt
    define('EM_REMOVESCHEDULEACTION', IPS_EVENTMESSAGE + 16); // Eintrag in der Aktionstabelle des Wochenplans wurde entfernt
    define('EM_CHANGESCHEDULEACTION', IPS_EVENTMESSAGE + 17); // Eintrag in der Aktionstabelle des Wochenplans hat sich geaendert
    define('EM_ADDSCHEDULEGROUP', IPS_EVENTMESSAGE + 18); // Gruppierung der Wochenplantage wurde hinzugefuegt
    define('EM_REMOVESCHEDULEGROUP', IPS_EVENTMESSAGE + 19); // Gruppierung der Wochenplantage wurde entfernt
    define('EM_CHANGESCHEDULEGROUP', IPS_EVENTMESSAGE + 20); // Gruppierung der Wochenplantage hat sich geaendert
    define('EM_ADDSCHEDULEGROUPPOINT', IPS_EVENTMESSAGE + 21); // Schaltpunkt einer Gruppierung wurde hinzugefuegt
    define('EM_REMOVESCHEDULEGROUPPOINT', IPS_EVENTMESSAGE + 22); // Schaltpunkt einer Gruppierung wurde entfernt
    define('EM_CHANGESCHEDULEGROUPPOINT', IPS_EVENTMESSAGE + 23); // Schaltpunkt einer Gruppierung hat sich geaendert

    // Medien Manager
    define('IPS_MEDIAMESSAGE', IPS_BASE + 900);
    define('MM_CREATE', IPS_MEDIAMESSAGE + 1); // Medienobjekt wurde erstellt
    define('MM_DELETE', IPS_MEDIAMESSAGE + 2); // Medienobjekt wurde entfernt
    define('MM_CHANGEFILE', IPS_MEDIAMESSAGE + 3); // Datei des Medienobjekts wurde geaendert
    define('MM_AVAILABLE', IPS_MEDIAMESSAGE + 4); // Verfuegbarkeit des Medienobjekts hat sich geaendert
    define('MM_UPDATE', IPS_MEDIAMESSAGE + 5); // Medienobjekt wurde aktualisiert
    define('MM_CHANGECACHED', IPS_MEDIAMESSAGE + 6); // Cacheoption vom Medienobjekt hat sich geaendert

    // Link Manager
    define('IPS_LINKMESSAGE', IPS_BASE + 1000);
    define('LM_CREATE', IPS_LINKMESSAGE + 1); // Link wurde erstellt
    define('LM_DELETE', IPS_LINKMESSAGE + 2); // Link wurde entfernt
    define('LM_CHANGETARGET', IPS_LINKMESSAGE + 3); // Ziel des Links hat sich geaendert

    // Flow Manager
    define('IPS_DATAMESSAGE', IPS_BASE + 1100);
    define('FM_CONNECT', IPS_DATAMESSAGE + 1); // Instanz wurde verbunden
    define('FM_DISCONNECT', IPS_DATAMESSAGE + 2); // Instanz wurde getrennt

    // Script Engine
    define('IPS_ENGINEMESSAGE', IPS_BASE + 1200);
    define('SE_UPDATE', IPS_ENGINEMESSAGE + 1); // Scriptengine wurde neu geladen
    define('SE_EXECUTE', IPS_ENGINEMESSAGE + 2); // Script wurde ausgefuehrt
    define('SE_RUNNING', IPS_ENGINEMESSAGE + 3); // Script wird ausgefuehrt

    // Profile Pool
    define('IPS_PROFILEMESSAGE', IPS_BASE + 1300);
    define('PM_CREATE', IPS_PROFILEMESSAGE + 1); // Profil wurde erstellt
    define('PM_DELETE', IPS_PROFILEMESSAGE + 2); // Profil wurde entfernt
    define('PM_CHANGETEXT', IPS_PROFILEMESSAGE + 3); // Profilprefix/Profilsuffix hat sich geaendert
    define('PM_CHANGEVALUES', IPS_PROFILEMESSAGE + 4); // Profilwerte haben sich geaendert
    define('PM_CHANGEDIGITS', IPS_PROFILEMESSAGE + 5); // Profilnachkommastellen haben sich geaendert
    define('PM_CHANGEICON', IPS_PROFILEMESSAGE + 6); // Profilicon hat sich geaendert
    define('PM_ASSOCIATIONADDED', IPS_PROFILEMESSAGE + 7); // Profilassoziation wurde hinzugefuegt
    define('PM_ASSOCIATIONREMOVED', IPS_PROFILEMESSAGE + 8); // Profilassoziation wurde entfernt
    define('PM_ASSOCIATIONCHANGED', IPS_PROFILEMESSAGE + 9); // Profilassoziation hat sich geaendert

    // Timer Pool
    define('IPS_TIMERMESSAGE', IPS_BASE + 1400);
    define('TM_REGISTER', IPS_TIMERMESSAGE + 1); // Timer wurde erstellt
    define('TM_UNREGISTER', IPS_TIMERMESSAGE + 2); // Timer wurde entfernt
    define('TM_SETINTERVAL', IPS_TIMERMESSAGE + 3); // Timer Interval hat sich geaendert
    define('TM_UPDATE', IPS_TIMERMESSAGE + 4); // Timer Fortschritt hat sich geaendert
    define('TM_RUNNING', IPS_TIMERMESSAGE + 5); // Timer Nachricht
}

/**
 * IP-Symcon status
 * https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/konfigurationsformulare/statusmeldung/
 */
if (@constant('IS_SBASE') == null) {
    // Status
    define('IS_SBASE', 100);
    define('IS_CREATING', IS_SBASE + 1); // Instanz wird erstellt
    define('IS_ACTIVE', IS_SBASE + 2); // Instanz ist aktiv
    define('IS_DELETING', IS_SBASE + 3); // Instanz wird geloescht
    define('IS_INACTIVE', IS_SBASE + 4); // Instanz ist inaktiv

    // Error
    define('IS_EBASE', 200); // Instanz ist fehlerhaft
}

?>
<?php

declare(strict_types=1);

/**
 * Sungrow Hybrid-Wechselrichter (SH-RT) ueber Modbus TCP (WiNet-S).
 * Registeradressen und Skalierung: mkaiser/Sungrow-SHx-Inverter-Modbus-Home-Assistant
 * (modbus_sungrow.yaml, Stand 2026-06-19), Adressen 0-basiert.
 */
class SungrowHybrid extends IPSModule
{
    // ====================================================================
    // Register
    // ====================================================================

    // Ident, Name, Typ (f/i/b), Funktion, Adresse, Datentyp, Skalierung, Profil
    private const READ = [
        ['SystemState',    'Systemstatus',                  'i', 4, 12999, 'u16', 1,    'SGH.State'],
        ['SoC',            'Batterie SoC',                  'f', 4, 13022, 'u16', 0.1,  'SGH.Percent'],
        ['SoH',            'Batterie SoH',                  'f', 4, 13023, 'u16', 0.1,  'SGH.Percent'],
        ['BatteryTemp',    'Batterie Temperatur',           'f', 4, 13024, 's16', 0.1,  '~Temperature'],
        ['BatteryVoltage', 'Batterie Spannung',             'f', 4, 13019, 'u16', 0.1,  '~Volt'],
        ['BatteryCurrent', 'Batterie Strom',                'f', 4, 5630,  's16', 0.1,  '~Ampere'],
        ['BatteryPower',   'Batterie Leistung (+Entl./-Laden)', 'f', 4, 5213, 's32', 1, '~Watt'],
        ['BatteryCapacity', 'Batterie Kapazitaet',          'f', 4, 5638,  'u16', 0.01, '~Electricity'],
        ['BmsMaxCharge',   'BMS max. Ladestrom',            'f', 4, 5634,  'u16', 1,    '~Ampere'],
        ['BmsMaxDischarge', 'BMS max. Entladestrom',        'f', 4, 5635,  'u16', 1,    '~Ampere'],
        ['PvPower',        'PV Leistung',                   'f', 4, 5016,  'u32', 1,    '~Watt'],
        ['Mppt1Voltage',   'MPPT1 Spannung',                'f', 4, 5010,  'u16', 0.1,  '~Volt'],
        ['Mppt1Current',   'MPPT1 Strom',                   'f', 4, 5011,  'u16', 0.1,  '~Ampere'],
        ['Mppt2Voltage',   'MPPT2 Spannung',                'f', 4, 5012,  'u16', 0.1,  '~Volt'],
        ['Mppt2Current',   'MPPT2 Strom',                   'f', 4, 5013,  'u16', 0.1,  '~Ampere'],
        ['LoadPower',      'Hausverbrauch',                 'f', 4, 13007, 's32', 1,    '~Watt'],
        ['MeterPower',     'Netz (+Bezug/-Einspeisung)',    'f', 4, 5600,  's32', 1,    '~Watt'],
        ['MeterPowerA',    'Netz Phase A',                  'f', 4, 5602,  's32', 1,    '~Watt'],
        ['MeterPowerB',    'Netz Phase B',                  'f', 4, 5604,  's32', 1,    '~Watt'],
        ['MeterPowerC',    'Netz Phase C',                  'f', 4, 5606,  's32', 1,    '~Watt'],
        ['BackupPower',    'Notstrom-Leistung',             'f', 4, 5725,  's32', 1,    '~Watt'],
        ['InverterTemp',   'Wechselrichter Temperatur',     'f', 4, 5007,  's16', 0.1,  '~Temperature'],
        ['GridFrequency',  'Netzfrequenz',                  'f', 4, 5241,  'u16', 0.01, '~Hertz'],
        ['DailyPv',        'PV heute',                      'f', 4, 13001, 'u16', 0.1,  '~Electricity'],
        ['TotalPv',        'PV gesamt',                     'f', 4, 13002, 'u32', 0.1,  '~Electricity'],
        ['DailyCharge',    'Batterie geladen heute',        'f', 4, 13039, 'u16', 0.1,  '~Electricity'],
        ['TotalCharge',    'Batterie geladen gesamt',       'f', 4, 13040, 'u32', 0.1,  '~Electricity'],
        ['DailyDischarge', 'Batterie entladen heute',       'f', 4, 13025, 'u16', 0.1,  '~Electricity'],
        ['TotalDischarge', 'Batterie entladen gesamt',      'f', 4, 13026, 'u32', 0.1,  '~Electricity'],
        ['DailyImport',    'Netzbezug heute',               'f', 4, 13035, 'u16', 0.1,  '~Electricity'],
        ['TotalImport',    'Netzbezug gesamt',              'f', 4, 13036, 'u32', 0.1,  '~Electricity'],
        ['DailyExport',    'Einspeisung heute',             'f', 4, 13044, 'u16', 0.1,  '~Electricity'],
        ['TotalExport',    'Einspeisung gesamt',            'f', 4, 13045, 'u32', 0.1,  '~Electricity'],
        ['MaxSoC',         'Max. SoC',                      'f', 3, 13057, 'u16', 0.1,  'SGH.Percent'],
        ['MinSoC',         'Min. SoC',                      'f', 3, 13058, 'u16', 0.1,  'SGH.Percent'],
        ['ReserveSoC',     'Notstromreserve SoC',           'f', 3, 13099, 'u16', 1,    'SGH.Percent'],
        ['BackupMode',     'Notstrom aktiv',                'b', 3, 13074, 'flag', 1,   '~Switch'],
        ['EMSMode',        'EMS-Modus',                     'i', 3, 13049, 'u16', 1,    'SGH.EMS'],
        ['ForcedCmd',      'Erzwungen: Befehl',             'i', 3, 13050, 'u16', 1,    'SGH.Forced'],
        ['ForcedPower',    'Erzwungen: Leistung',           'f', 3, 13051, 'u16', 1,    '~Watt'],
        ['ExportLimit',    'Einspeiselimit',                'f', 3, 13073, 'u16', 1,    '~Watt'],
        ['ExportLimitMode', 'Einspeiselimit aktiv',         'b', 3, 13086, 'flag', 1,   '~Switch'],
        ['MaxChargePower', 'Max. Ladeleistung',             'f', 3, 33046, 'u16', 10,   '~Watt'],
        ['MaxDischargePower', 'Max. Entladeleistung',       'f', 3, 33047, 'u16', 10,   '~Watt'],
    ];

    // Ident => Adresse, Skalierung, min, max, erlaubte Werte (leer = Bereich)
    private const WRITE = [
        'MaxSoC'            => [13057, 0.1, 50, 100, []],
        'MinSoC'            => [13058, 0.1, 0, 50, []],
        'ReserveSoC'        => [13099, 1, 0, 100, []],
        'BackupMode'        => [13074, 1, 0, 1, []],
        'EMSMode'           => [13049, 1, 0, 2, [0, 2]],
        'ForcedCmd'         => [13050, 1, 170, 204, [170, 187, 204]],
        'ForcedPower'       => [13051, 1, 0, 10000, []],
        'ExportLimit'       => [13073, 1, 0, 10000, []],
        'ExportLimitMode'   => [13086, 1, 0, 1, []],
        'MaxChargePower'    => [33046, 10, 10, 10600, []],
        'MaxDischargePower' => [33047, 10, 10, 10600, []],
    ];

    private const FLAG_ON = 0xAA;
    private const FLAG_OFF = 0x55;

    private $socket = null;
    private int $tid = 0;

    // ====================================================================
    // Symcon
    // ====================================================================

    public function Create(): void
    {
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyInteger('Port', 502);
        $this->RegisterPropertyInteger('Unit', 1);
        $this->RegisterPropertyInteger('Interval', 30);
        $this->RegisterPropertyBoolean('AllowWrite', false);
        $this->RegisterTimer('SghUpdate', 0, 'SGH_Update($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();
        $this->createProfiles();

        foreach (self::READ as $pos => [$ident, $name, $type, , , , , $profile]) {
            match ($type) {
                'b' => $this->RegisterVariableBoolean($ident, $name, $profile, $pos + 1),
                'i' => $this->RegisterVariableInteger($ident, $name, $profile, $pos + 1),
                default => $this->RegisterVariableFloat($ident, $name, $profile, $pos + 1),
            };
        }
        foreach (array_keys(self::WRITE) as $ident) {
            if ($this->ReadPropertyBoolean('AllowWrite')) {
                $this->EnableAction($ident);
            } else {
                $this->DisableAction($ident);
            }
        }

        if ($this->ReadPropertyString('Host') === '') {
            $this->SetTimerInterval('SghUpdate', 0);
            $this->SetStatus(104);
            return;
        }
        $this->SetTimerInterval('SghUpdate', max(5, $this->ReadPropertyInteger('Interval')) * 1000);
        $this->SetStatus(102);
    }

    public function RequestAction($Ident, $Value)
    {
        if (!isset(self::WRITE[$Ident])) {
            throw new Exception("Unbekannte Aktion: $Ident");
        }
        if (!$this->ReadPropertyBoolean('AllowWrite')) {
            throw new Exception('Schreiben ist in der Instanz nicht freigegeben');
        }
        $address = self::WRITE[$Ident][0];
        $raw = $this->validate((string) $Ident, $Value);
        $done = $this->withConnection(function () use ($Ident, $address, $raw) {
            $current = $this->readRegisters(3, $address, 1);
            if ($current === null) {
                throw new Exception('Aktueller Wert nicht lesbar');
            }
            // ponytail: nur bei Aenderung schreiben, schont den Flash des Wechselrichters
            if ($current[0] !== $raw && !$this->writeRegister($address, $raw)) {
                throw new Exception("Schreiben von $Ident abgelehnt");
            }
            $check = $this->readRegisters(3, $address, 1);
            if ($check === null || $check[0] !== $raw) {
                throw new Exception("$Ident wurde nicht uebernommen");
            }
            return true;
        });
        if ($done !== true) {
            throw new Exception('Keine Verbindung zum Wechselrichter');
        }
        $this->SetValue($Ident, is_bool($Value) ? $Value : (is_int($this->GetValue($Ident)) ? (int) $Value : (float) $Value));
    }

    public function Update(): bool
    {
        if ($this->ReadPropertyString('Host') === '') {
            return false;
        }
        return $this->withConnection(function () {
            foreach (self::READ as [$ident, , $type, $fc, $address, $dtype, $scale]) {
                $words = $this->readRegisters($fc, $address, $dtype[1] === '3' ? 2 : 1);
                if ($words === null) {
                    $this->SendDebug('Modbus', "Register $address nicht lesbar", 0);
                    $this->SetStatus(201);
                    return false;
                }
                $value = self::decode($words, $dtype, (float) $scale);
                $value = is_bool($value) ? $value : round($value, 3);
                $this->SetValue($ident, $type === 'i' ? (int) $value : ($type === 'b' ? (bool) $value : (float) $value));
            }
            $this->SetStatus(102);
            return true;
        });
    }

    // ====================================================================
    // Pruefung und Umrechnung
    // ====================================================================

    /** Wandelt die Register nach Sungrow-Wortfolge (Low-Word zuerst) in einen Wert. */
    public static function decode(array $w, string $dtype, float $scale)
    {
        switch ($dtype) {
            case 'flag':
                return $w[0] === self::FLAG_ON;
            case 'u32':
                return (($w[1] << 16) | $w[0]) * $scale;
            case 's32':
                $v = ($w[1] << 16) | $w[0];
                return ($v >= 0x80000000 ? $v - 0x100000000 : $v) * $scale;
            case 's16':
                return ($w[0] >= 0x8000 ? $w[0] - 0x10000 : $w[0]) * $scale;
            default:
                return $w[0] * $scale;
        }
    }

    /** Prueft Grenzwerte, liefert den Rohwert oder wirft eine Exception. */
    private function validate(string $ident, $value): int
    {
        [, $scale, $min, $max, $allowed] = self::WRITE[$ident];
        if ($ident === 'BackupMode' || $ident === 'ExportLimitMode') {
            return $value ? self::FLAG_ON : self::FLAG_OFF;
        }
        $number = (float) $value;
        if ($number < $min || $number > $max || ($allowed && !in_array((int) $number, $allowed, true))) {
            throw new Exception("$ident: Wert $number ausserhalb des Bereichs");
        }
        if ($ident === 'MinSoC' && $number >= $this->GetValue('MaxSoC')) {
            throw new Exception('Min. SoC muss unter Max. SoC liegen');
        }
        if ($ident === 'MaxSoC' && $number <= $this->GetValue('MinSoC')) {
            throw new Exception('Max. SoC muss ueber Min. SoC liegen');
        }
        return (int) round($number / $scale);
    }

    private function createProfiles(): void
    {
        $this->profile('SGH.Percent', 2, ' %', 0, 100, 1, []);
        $this->profile('SGH.EMS', 1, '', 0, 0, 0, [[0, 'Eigenverbrauch'], [2, 'Erzwungen'], [3, 'Externes EMS'], [4, 'VPP']]);
        $this->profile('SGH.Forced', 1, '', 0, 0, 0, [[170, 'Laden'], [187, 'Entladen'], [204, 'Stopp']]);
        $this->profile('SGH.State', 1, '', 0, 0, 0, [[0, 'Betrieb'], [1, 'Stopp'], [8, 'Standby'], [16, 'Init-Standby'], [32, 'Startet'], [64, 'Betrieb'], [128, 'Derating']]);
    }

    private function profile(string $name, int $type, string $suffix, float $min, float $max, float $step, array $assoc): void
    {
        if (IPS_VariableProfileExists($name)) {
            return;
        }
        IPS_CreateVariableProfile($name, $type);
        IPS_SetVariableProfileText($name, '', $suffix);
        IPS_SetVariableProfileValues($name, $min, $max, $step);
        foreach ($assoc as [$value, $text]) {
            IPS_SetVariableProfileAssociation($name, $value, $text, '', -1);
        }
    }

    // ====================================================================
    // Modbus TCP
    // ====================================================================

    private function withConnection(callable $work)
    {
        $lock = 'SGH_' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($lock, 15000)) {
            return false;
        }
        try {
            $this->socket = @fsockopen($this->ReadPropertyString('Host'), $this->ReadPropertyInteger('Port'), $errno, $errstr, 3);
            if (!$this->socket) {
                $this->SendDebug('Modbus', "Verbindung fehlgeschlagen: $errstr ($errno)", 0);
                $this->SetStatus(200);
                return false;
            }
            stream_set_timeout($this->socket, 4);
            return $work();
        } finally {
            if ($this->socket) {
                fclose($this->socket);
                $this->socket = null;
            }
            IPS_SemaphoreLeave($lock);
        }
    }

    private function transact(string $pdu): ?string
    {
        $this->tid = ($this->tid + 1) & 0xFFFF;
        fwrite($this->socket, pack('nnnC', $this->tid, 0, strlen($pdu) + 1, $this->ReadPropertyInteger('Unit')) . $pdu);
        $head = $this->receive(7);
        if ($head === null) {
            return null;
        }
        $body = $this->receive(unpack('n', $head, 4)[1] - 1);
        if ($body === null || (ord($body[0]) & 0x80)) {
            $this->SendDebug('Modbus', 'Keine gueltige Antwort', 0);
            return null;
        }
        return $body;
    }

    private function receive(int $length): ?string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === false || $chunk === '') {
                return null;
            }
            $data .= $chunk;
        }
        return $data;
    }

    private function readRegisters(int $fc, int $address, int $count): ?array
    {
        $body = $this->transact(pack('Cnn', $fc, $address, $count));
        if ($body === null || strlen($body) !== 2 + 2 * $count) {
            return null;
        }
        return array_values(unpack('n*', substr($body, 2)));
    }

    private function writeRegister(int $address, int $value): bool
    {
        return $this->transact(pack('Cnn', 6, $address, $value)) !== null;
    }
}

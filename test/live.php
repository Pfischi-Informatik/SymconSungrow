<?php

declare(strict_types=1);

/**
 * Live-Test ohne Symcon. Stellt IPSModule und die IPS-Funktionen minimal nach.
 * Nach test/live.php kopieren, unten Modulpfad, Klasse und Pruefungen anpassen.
 *
 *   php -d zend.assertions=1 -d assert.exception=1 test/live.php [args]
 *   DEBUG=1 ... zeigt SendDebug-Ausgaben
 */

const VARIABLETYPE_BOOLEAN = 0;
const VARIABLETYPE_INTEGER = 1;
const VARIABLETYPE_FLOAT = 2;
const VARIABLETYPE_STRING = 3;
const KL_MESSAGE = 10206;
const KL_WARNING = 10;
const KL_ERROR = 20;
const KR_READY = 10103;
function IPS_VariableProfileExists($n) { global $profiles; return isset($profiles[$n]); }
function IPS_CreateVariableProfile($n, $t) { global $profiles; $profiles[$n] = []; }
function IPS_DeleteVariableProfile($n) { global $profiles; unset($profiles[$n]); }
function IPS_SetVariableProfileAssociation($n, $v, $name, $i, $c) { global $profiles; $profiles[$n][$v] = $name; }
function IPS_SetVariableProfileValues($n, $min, $max, $step) {}
function IPS_SetVariableProfileText($n, $prefix, $suffix) {}
function IPS_SetVariableProfileIcon($n, $icon) {}
function IPS_GetKernelRunlevel() { return KR_READY; }

class IPSModule
{
    public $InstanceID = 12345;
    private $props = [], $attrs = [], $values = [];
    public $status = 0;
    public function Create(): void {}
    public function Destroy(): void {}
    public function ApplyChanges(): void {}
    protected function RegisterPropertyString($n, $v) { $this->props[$n] = $v; }
    protected function RegisterPropertyInteger($n, $v) { $this->props[$n] = $v; }
    protected function RegisterPropertyBoolean($n, $v) { $this->props[$n] = $v; }
    protected function RegisterPropertyFloat($n, $v) { $this->props[$n] = $v; }
    public function SetProperty($n, $v) { $this->props[$n] = $v; }
    protected function ReadPropertyString($n) { return $this->props[$n]; }
    protected function ReadPropertyInteger($n) { return $this->props[$n]; }
    protected function ReadPropertyBoolean($n) { return $this->props[$n]; }
    protected function ReadPropertyFloat($n) { return $this->props[$n]; }
    protected function RegisterAttributeString($n, $v) { $this->attrs[$n] = $v; }
    protected function RegisterAttributeInteger($n, $v) { $this->attrs[$n] = $v; }
    protected function RegisterAttributeBoolean($n, $v) { $this->attrs[$n] = $v; }
    public function ReadAttributeString($n) { return $this->attrs[$n]; }
    public function ReadAttributeInteger($n) { return $this->attrs[$n]; }
    public function ReadAttributeBoolean($n) { return $this->attrs[$n]; }
    public function WriteAttributeString($n, $v) { $this->attrs[$n] = $v; }
    public function WriteAttributeInteger($n, $v) { $this->attrs[$n] = $v; }
    public function WriteAttributeBoolean($n, $v) { $this->attrs[$n] = $v; }
    protected function RegisterTimer($n, $i, $s) {}
    protected function SetTimerInterval($n, $i) {}
    protected function RegisterVariableBoolean($i, $n, $p = '', $pos = 0) { $this->values[$i] ??= false; }
    protected function RegisterVariableInteger($i, $n, $p = '', $pos = 0) { $this->values[$i] ??= 0; }
    protected function RegisterVariableFloat($i, $n, $p = '', $pos = 0) { $this->values[$i] ??= 0.0; }
    protected function RegisterVariableString($i, $n, $p = '', $pos = 0) { $this->values[$i] ??= ''; }
    protected function EnableAction($i) {}
    protected function DisableAction($i) {}
    protected function SetValue($i, $v) { $this->values[$i] = $v; }
    public function GetValue($i) { return $this->values[$i]; }
    protected function SetStatus($s) { $this->status = $s; }
    protected function Translate($s) { return $s; }
    protected function LogMessage($m, $t) { fwrite(STDERR, "LOG: $m\n"); }
    protected function SendDebug($t, $m, $f) { if (getenv('DEBUG')) fwrite(STDERR, "DEBUG $t: " . substr((string) $m, 0, 400) . "\n"); }
}

// --- Sungrow -------------------------------------------------------------

function IPS_SemaphoreEnter($n, $t) { return true; }
function IPS_SemaphoreLeave($n) { return true; }

require __DIR__ . '/../SungrowHybrid/module.php';

// Decodierung
assert(SungrowHybrid::decode([0xFCF0, 0xFFFF], 's32', 1.0) === -784.0, 'Batterie laden negativ');
assert(SungrowHybrid::decode([0x10, 1], 'u32', 1.0) === 65552.0, 'Low-Word zuerst');
assert(SungrowHybrid::decode([0xFFFF], 's16', 0.1) === -0.1, 's16');
assert(SungrowHybrid::decode([0xAA], 'flag', 1.0) === true && SungrowHybrid::decode([0x55], 'flag', 1.0) === false, 'flag');

$m = new SungrowHybrid();
$m->Create();
$m->SetProperty('Host', $argv[1] ?? '');
$m->ApplyChanges();

// Schreibschutz greift, ohne dass eine Verbindung aufgebaut wird
$threw = false;
try { $m->RequestAction('MaxSoC', 90.0); } catch (Exception $e) { $threw = true; }
assert($threw, 'Schreiben ohne Freigabe muss scheitern');

// Grenzwerte: nur Pruefung, kein Socket, Ausnahme vor dem Verbinden
$m->SetProperty('AllowWrite', true);
foreach ([['MaxSoC', 30.0], ['MinSoC', 60.0], ['EMSMode', 1], ['ForcedCmd', 99], ['MaxChargePower', 20000.0]] as [$i, $v]) {
    $threw = false;
    try { $m->RequestAction($i, $v); } catch (Exception $e) { $threw = true; }
    assert($threw, "$i=$v muss abgelehnt werden");
}
$m->SetProperty('AllowWrite', false);

if (isset($argv[1])) {
    assert($m->Update() === true, 'Abfrage');
    assert($m->status === 102, 'Status');
    foreach (['SystemState','SoC','SoH','BatteryTemp','BatteryVoltage','BatteryCurrent','BatteryPower','BatteryCapacity','PvPower','LoadPower','MeterPower','MaxSoC','MinSoC','ReserveSoC','BackupMode','EMSMode','ForcedCmd','ExportLimit','ExportLimitMode','MaxChargePower','TotalCharge','TotalDischarge','TotalImport','TotalExport'] as $i) {
        echo str_pad($i, 18), var_export($m->GetValue($i), true), "\n";
    }
}
echo "OK\n";

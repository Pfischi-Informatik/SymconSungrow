# Sungrow Hybrid fuer IP-Symcon

Liest einen Sungrow SH-RT Hybrid-Wechselrichter (getestet: SH10RT) ueber Modbus TCP
(WiNet-S, Port 502, Unit 1) und kann Batterie-Grenzwerte schreiben.

## Installation

Ordner nach `/Library/Application Support/Symcon/modules/SymconSungrow` kopieren, dort
`git init` und einen Commit anlegen, Symcon-Dienst neu starten. Instanz "Sungrow Hybrid"
anlegen und die IP eintragen.

## Variablen

Status, Batterie (SoC, SoH, Temperatur, Spannung, Strom, Leistung, Kapazitaet, BMS-Limits),
PV, MPPT1/2, Hausverbrauch, Netz gesamt und je Phase, Notstrom, Temperatur, Frequenz,
Tages- und Gesamtzaehler.

Vorzeichen: Batterie `+` Entladen / `-` Laden, Netz `+` Bezug / `-` Einspeisung.

## Schreiben

Standardmaessig gesperrt. Erst mit dem Haken "Schreiben erlauben" sind diese Variablen
schaltbar, aus Skripten per `RequestAction($VariablenID, $Wert)`:

| Variable | Bereich |
|---|---|
| Max. SoC | 50 bis 100 % |
| Min. SoC | 0 bis 50 %, unter Max. SoC |
| Notstromreserve SoC | 0 bis 100 % |
| Notstrom aktiv | Ein/Aus |
| EMS-Modus | Eigenverbrauch, Erzwungen |
| Erzwungen: Befehl, Leistung | Laden/Entladen/Stopp, 0 bis 10000 W |
| Einspeiselimit, aktiv | 0 bis 10000 W, Ein/Aus |
| Max. Lade-/Entladeleistung | 10 bis 10600 W |

Jeder Schreibvorgang wird zurueckgelesen. Unveraenderte Werte werden nicht geschrieben.

## Funktionen

`SGH_Update(int $InstanzID): bool` liest alle Register neu.

## Test

```bash
php -d zend.assertions=1 -d assert.exception=1 test/live.php [IP]
```

Ohne IP nur Umrechnung und Grenzwerte, mit IP zusaetzlich eine reine Leseabfrage.

Registerquelle: [mkaiser/Sungrow-SHx-Inverter-Modbus-Home-Assistant](https://github.com/mkaiser/Sungrow-SHx-Inverter-Modbus-Home-Assistant).

# Pflichtenheft: Geräteinformationen als User Stories

## 1. Ziel und Abgrenzung

Dieses Pflichtenheft beschreibt die Anforderungen an das Konsolenbeispiel
`examples/02_device_info.php`. Das Beispiel soll einem Administrator einen
schnellen, ausschließlich lesenden Überblick über ein Anviz-Gerät geben. Es
verbindet sich per TCP/IP, liest Geräte-, Zeit-, Netzwerk- und
Aufzeichnungsdaten aus, gibt sie strukturiert aus und beendet die Verbindung
anschließend kontrolliert.

Nicht Bestandteil dieses Anwendungsfalls sind Änderungen an der
Gerätekonfiguration, das Herunterladen einzelner Anwesenheitsdatensätze sowie
die Benutzer- oder Zutrittsverwaltung.

## 2. Grundlagen

Die Anforderungen basieren auf:

- `examples/02_device_info.php`
- `README.md`
- `docs/API.md`
- `docs/PROTOCOL.md`
- `docs/INSTALLATION.md`

Technische Randbedingungen:

- PHP 7.4 oder neuer
- aktivierte PHP-Erweiterung `ext-sockets`
- per Composer installierte Abhängigkeiten und Autoloader
- TCP-Erreichbarkeit des Geräts, standardmäßig über Port 5010
- synchroner Request-Response-Betrieb über das Anviz-Protokoll

## 3. Rollen

**Administrator**  
Betreibt und prüft Anviz-Geräte und benötigt eine kompakte Übersicht über
Identität, Kapazitäten, Zeit, Netzwerk und Aufzeichnungen.

**Support- oder Integrationsentwickler**  
Nutzt die Ausgabe zur Inbetriebnahme, Diagnose und Prüfung der SDK-Anbindung.

## 4. User Stories

### US-01 – Verbindungsparameter konfigurieren

**Als** Administrator  
**möchte ich** Zielgerät und Verbindungsparameter über Umgebungsvariablen
festlegen können,  
**damit** ich dasselbe Beispiel ohne Quellcodeänderung für unterschiedliche
Geräte verwenden kann.

**Akzeptanzkriterien**

1. Das System unterstützt die Variablen `DEVICE_IP`, `DEVICE_PORT`,
   `DEVICE_ID` und `DEVICE_PASSWORD`.
2. Fehlt eine Variable, werden folgende Standardwerte verwendet:
   `192.168.2.57`, Port `5010`, Geräte-ID `8541` und kein Passwort.
3. Port und Geräte-ID werden als Ganzzahlen an `AnvizClient` übergeben.
4. Ein gesetztes `DEVICE_PASSWORD` wird als fünfter Konstruktorparameter an
   `AnvizClient` übergeben und beim Verbindungsaufbau berücksichtigt.
5. Ein fehlendes oder leeres Passwort löst keinen Authentifizierungsversuch
   aus.
6. Hostname beziehungsweise IP-Adresse dürfen nicht leer sein, der Port muss
   zwischen `1` und `65535` liegen und die Geräte-ID muss im vom Protokoll
   unterstützten Wertebereich liegen. Ungültige Werte werden vor dem Aufbau
   des Sockets mit einer verständlichen Fehlermeldung abgelehnt.
7. Die für das Beispiel geltenden Variablennamen werden in
   `.env.example` und `docs/INSTALLATION.md` identisch dokumentiert.

### US-02 – Verbindung zum Gerät herstellen

**Als** Administrator  
**möchte ich** eine TCP/IP-Verbindung zum konfigurierten Gerät herstellen,  
**damit** die Geräteinformationen zuverlässig abgefragt werden können.

**Akzeptanzkriterien**

1. Vor der ersten Abfrage wird `connect()` aufgerufen.
2. Für die Verbindung gilt ein Timeout von 10 Sekunden.
3. Die Kommunikation verwendet das Anviz-Nachrichtenformat mit Präambel,
   Geräte-ID, Befehl beziehungsweise ACK, Datenlänge und CRC16-Prüfsumme.
4. Antworten mit ungültiger CRC, Fehler-RET oder ohne Nutzdaten werden nicht
   als erfolgreicher Abruf behandelt.
5. Bei einem Kommunikationsfehler endet das Programm mit Exit-Code `1` und
   einer verständlichen Fehlermeldung.
6. Der Rückgabewert von `connect()` wird geprüft. Die erste Geräteabfrage darf
   nur erfolgen, wenn `connect()` den Wert `true` geliefert hat und der Client
   verbunden ist.

### US-03 – Geräteidentität anzeigen

**Als** Administrator  
**möchte ich** die Identität und Softwareversion des Geräts sehen,  
**damit** ich das angesprochene Gerät eindeutig prüfen kann.

**Akzeptanzkriterien**

1. Unter der Überschrift `GERÄTEKONFIGURATION` werden Geräte-ID, Modell,
   Firmware-Version und Seriennummer ausgegeben.
2. Die Geräte-ID entspricht der für die Sitzung konfigurierten Geräte-ID.
3. Die Firmware wird über die Basisinformationen, Protokollbefehl `0x30`,
   ermittelt.
4. Die Seriennummer wird über Protokollbefehl `0x24` ermittelt.
5. Das Modell wird über Protokollbefehl `0x48` ermittelt.
6. Die Ausgabe enthält für jedes Feld eine eindeutige Beschriftung und einen
   Wert.
7. Das bereits durch `getDeviceInfo()` geladene Modell wird aus dem
   `DeviceInfo`-Objekt ausgegeben. Es wird nicht durch einen zweiten Aufruf von
   `getDeviceModel()` erneut vom Gerät abgefragt.

### US-04 – Gerätekapazitäten anzeigen

**Als** Administrator  
**möchte ich** die maximalen Kapazitäten des Geräts sehen,  
**damit** ich seine Eignung und mögliche Engpässe einschätzen kann.

**Akzeptanzkriterien**

1. Unter der Überschrift `KAPAZITÄTEN` werden Benutzer-, Fingerabdruck- und
   Aufzeichnungskapazität als nicht negative Ganzzahlen ausgegeben.
2. Die Kapazitäten werden aus den erweiterten Geräteinformationen des
   Protokollbefehls `0x34` gelesen.
3. Fehlende oder unvollständige Gerätedaten dürfen nicht als plausibler
   erfolgreicher Messwert dargestellt werden; der Fehler muss erkennbar sein.
4. Ein fehlgeschlagener Teilabruf darf nicht stillschweigend durch den Wert
   `0` oder eine leere Zeichenkette ersetzt werden. Die Ausgabe kennzeichnet
   den betroffenen Wert als nicht verfügbar oder die Gesamtabfrage schlägt
   kontrolliert fehl.

### US-05 – Gerätezeit anzeigen

**Als** Administrator  
**möchte ich** die aktuelle Uhrzeit des Geräts zusammen mit der verwendeten
PHP-Zeitzone sehen,  
**damit** ich Zeitabweichungen bei Anwesenheitsbuchungen erkennen kann.

**Akzeptanzkriterien**

1. Die Gerätezeit wird über Protokollbefehl `0x38` abgerufen.
2. Das sechs Byte lange Geräteformat `YY MM DD HH mm ss` wird als Datum im
   Bereich 2000 bis 2099 interpretiert.
3. Die Ausgabe erfolgt unter `SYSTEM ZEIT` im Format `TT.MM.JJJJ HH:MM:SS`.
4. Zusätzlich wird die aktive PHP-Zeitzone aus
   `date_default_timezone_get()` angezeigt.
5. Ungültige oder unvollständige Datumsdaten führen zu einer Fehlermeldung.
6. Kalendertag und Uhrzeit werden strikt validiert; ungültige Werte wie der
   31. Februar werden nicht automatisch in ein anderes Datum normalisiert.

### US-06 – Netzwerkkonfiguration anzeigen

**Als** Support- oder Integrationsentwickler  
**möchte ich** die Netzwerkkonfiguration des Geräts sehen,  
**damit** ich Adressierungs- und Verbindungsprobleme diagnostizieren kann.

**Akzeptanzkriterien**

1. Die Netzwerkkonfiguration wird über Protokollbefehl `0x3A` abgerufen.
2. Unter `NETZWERK` werden Hostname des ausführenden Systems, IP-Adresse,
   Netzmaske, Gateway und MAC-Adresse ausgegeben.
3. IPv4-Adressen werden in Punktnotation und die MAC-Adresse als sechs
   zweistellige Hexadezimalwerte ausgegeben.
4. Server-IP und Server-Port werden nur angezeigt, wenn die Server-IP nicht
   `0.0.0.0` ist.
5. Die Antwort wird entsprechend der dokumentierten 27-Byte-Struktur
   ausgewertet.
6. Eine zu kurze Netzwerkantwort wird nicht als gültige Konfiguration mit
   Ersatzwerten wie `0.0.0.0` oder `00:00:00:00:00:00` dargestellt.

### US-07 – Aufzeichnungsstatus und Auslastung anzeigen

**Als** Administrator  
**möchte ich** Anzahl und Auslastung der Aufzeichnungen sehen,  
**damit** ich einen drohenden Speicherengpass frühzeitig erkenne.

**Akzeptanzkriterien**

1. Die Aufzeichnungsstatistik wird über Protokollbefehl `0x3C` abgerufen.
2. Unter `AUFZEICHNUNGEN` werden Gesamtzahl und Anzahl neuer Aufzeichnungen
   ausgegeben.
3. Die Aufzeichnungskapazität stammt aus den erweiterten Geräteinformationen
   des Protokollbefehls `0x34` und wird ebenfalls ausgegeben.
4. Die Auslastung wird als
   `Gesamtaufzeichnungen / Aufzeichnungskapazität * 100` berechnet und mit
   einer Nachkommastelle in Prozent dargestellt.
5. Bei Kapazität `0` findet keine Division statt; stattdessen wird die
   Auslastung als nicht verfügbar gekennzeichnet.
6. Die vom Beispiel erwartete Kapazität wird entweder ausdrücklich in das
   Ergebnis von `getRecordInfo()` aufgenommen oder aus dem bereits geladenen
   `DeviceInfo`-Objekt bezogen. Die gewählte Datenquelle wird konsistent
   verwendet.

### US-08 – Sitzung sauber beenden

**Als** Betreiber  
**möchte ich** sicher sein, dass die Netzwerkverbindung nach der Abfrage
geschlossen wird,  
**damit** keine unnötigen offenen Verbindungen am Gerät verbleiben.

**Akzeptanzkriterien**

1. Nach erfolgreicher Ausgabe wird `disconnect()` aufgerufen.
2. Das Programm bestätigt den erfolgreichen Abschluss mit `✓ Erfolgreich`
   und Exit-Code `0`.
3. Auch nach einer Exception wird eine bereits aufgebaute Verbindung nach
   Möglichkeit geschlossen, bevor das Programm endet.
4. Im Fehlerfall beginnt die Abschlussmeldung mit `✗ Fehler:` und enthält die
   Ursache, jedoch kein Kommunikationspasswort.
5. Die Bereinigung erfolgt in einem `finally`-Pfad oder einer gleichwertigen
   Struktur und gilt auch für PHP-Fehler vom Typ `Throwable`, insbesondere
   Rechen- und Typfehler.

### US-09 – Lesbare Konsolenausgabe bereitstellen

**Als** Administrator  
**möchte ich** die Ergebnisse in einer einheitlich gegliederten Ausgabe
erhalten,  
**damit** ich die Werte schnell erfassen und vergleichen kann.

**Akzeptanzkriterien**

1. Die Ausgabe beginnt mit `ANVIZ GERÄTEINFORMATIONEN`.
2. Die Bereiche erscheinen in der Reihenfolge Gerätekonfiguration,
   Kapazitäten, Systemzeit, Netzwerk und Aufzeichnungen.
3. Zahlen werden dezimal ausgegeben; die Auslastung enthält ein Prozentzeichen.
4. Überschriften und Feldnamen sind deutsch und konsistent formatiert.
5. Diagnose-Logs dürfen die fachliche Ausgabe nicht unlesbar machen und keine
   Zugangsdaten enthalten.
6. Die deutsche Feldbezeichnung `Modell` wird einheitlich verwendet.
7. Die Ausgabe ist UTF-8-kodiert, sodass Umlaute sowie `✓` und `✗` korrekt
   dargestellt werden.

### US-10 – Konsistenten Informationsstand liefern

**Als** Support- oder Integrationsentwickler  
**möchte ich** einen in sich konsistenten Informationsstand mit erkennbaren
Teilfehlern erhalten,  
**damit** ich Nullwerte nicht irrtümlich als reale Gerätewerte interpretiere.

**Akzeptanzkriterien**

1. Jeder benötigte Protokollbefehl wird pro Ausführung nur einmal gesendet,
   sofern kein dokumentierter Wiederholungsversuch nach einem
   Kommunikationsfehler erforderlich ist.
2. Bereits in `DeviceInfo` vorhandene Identitäts-, Kapazitäts- und
   Statistikwerte werden wiederverwendet.
3. Ein Teilfehler wird mit betroffenem Befehl oder Feld erkennbar gemeldet.
4. Die Erfolgsmeldung erscheint nur, wenn alle als verpflichtend definierten
   Informationsbereiche vollständig gelesen und ausgegeben wurden.
5. Werte, die zu unterschiedlichen Zeitpunkten gelesen werden müssen, werden
   in einer festen Reihenfolge und innerhalb derselben Verbindung abgefragt.

### US-11 – Zugangsdaten vor Protokollierung schützen

**Als** Betreiber  
**möchte ich** das Kommunikationspasswort auch bei aktiviertem Debug-Logging
geschützt wissen,  
**damit** Zugangsdaten weder im Terminal noch in Logdateien offengelegt
werden.

**Akzeptanzkriterien**

1. Request-Dumps des Authentifizierungsbefehls `0x04` enthalten weder das
   Passwort im Klartext noch dessen Hexadezimaldarstellung.
2. Das Passwort wird vor der Protokollierung entfernt oder der Payload dieses
   Befehls vollständig maskiert.
3. Bibliothekscode überschreibt nicht ungefragt den vom Aufrufer gewählten
   Log-Level.
4. Standardmäßig erscheinen keine binären Debug-Paketdumps in der fachlichen
   Konsolenausgabe.

### US-12 – Verhalten automatisiert prüfen

**Als** Entwickler  
**möchte ich** das Beispiel ohne physisches Gerät automatisiert prüfen
können,  
**damit** Regressionen bei Ausgabe, Fehlerbehandlung und Berechnungen früh
erkannt werden.

**Akzeptanzkriterien**

1. Geräteantworten beziehungsweise die öffentliche Client-API können in Tests
   durch kontrollierte Testdaten ersetzt werden.
2. Tests decken mindestens Erfolgsausgabe, Verbindungsfehler, Teilantwort,
   Kapazität `0`, optionale Server-IP und Passwortübergabe ab.
3. Ein Test stellt sicher, dass Geheimnisse in keinem Logkanal vorkommen.
4. Syntaxprüfung, Unit-Tests und statische Analyse sind lokal über
   dokumentierte Composer-Kommandos ausführbar.
5. Für die Abnahme am realen Gerät existiert ein Integrationstest mit den
   verwendeten Befehlen und ohne schreibende Geräteoperationen.

## 5. Nichtfunktionale Anforderungen

### Sicherheit

- Das Kommunikationspasswort darf weder in der Standardausgabe noch in
  Fehlermeldungen oder Debug-Logs erscheinen.
- Das Beispiel führt ausschließlich lesende Gerätebefehle aus.
- Netzwerk- und Geräteparameter werden nicht verändert.
- Authentifizierungs-Payloads werden auch bei Debug-Logging maskiert.

### Zuverlässigkeit

- Jede Antwort wird durch die SDK-Protokollschicht auf CRC16 und RET-Code
  geprüft.
- Die Verbindung wird für die Abfragen einer Ausführung wiederverwendet.
- Das Programm liefert über seinen Exit-Code ein maschinell auswertbares
  Ergebnis.
- Teilabrufe werden nicht als vollständiger Erfolg ausgegeben.

### Kompatibilität

- Das Beispiel bleibt mit PHP 7.4 und der in `composer.json` definierten
  PSR-4-Struktur kompatibel.
- Die Implementierung unterstützt Anviz-TCP/IP-Geräte, welche die verwendeten
  Befehle `0x24`, `0x30`, `0x34`, `0x38`, `0x3A`, `0x3C` und `0x48`
  bereitstellen.

### Wartbarkeit

- Der Zugriff auf das Gerät erfolgt ausschließlich über die öffentliche API
  von `AnvizClient` und die Modelle `DeviceInfo` und `NetworkConfig`.
- Feldbezeichnungen und Berechnungsregeln sind zentral und nachvollziehbar im
  Beispielcode abgebildet.

## 6. Fachlicher Ablauf

1. Autoloader laden und Verbindungsparameter bestimmen.
2. `AnvizClient` mit Host, Port, Geräte-ID, Timeout und optionalem Passwort
   erzeugen.
3. Verbindung herstellen.
4. Geräteinformationen und Kapazitäten lesen und ausgeben.
5. Gerätezeit lesen und ausgeben.
6. Netzwerkkonfiguration lesen und ausgeben.
7. Aufzeichnungsstatistik lesen, Auslastung berechnen und ausgeben.
8. Verbindung schließen und Erfolg melden.
9. Bei einem Fehler die Verbindung schließen, Fehler melden und Exit-Code `1`
   liefern.

## 7. Definition of Done

Die Funktion gilt als abgenommen, wenn alle Akzeptanzkriterien der User Stories
US-01 bis US-12 erfüllt sind, die Ausführung mit einem erreichbaren
kompatiblen Gerät erfolgreich endet und folgende Fehlerfälle nachvollziehbar
behandelt werden:

- Gerät nicht erreichbar oder Timeout
- fehlerhafte CRC beziehungsweise Fehler-RET
- ungültige oder unvollständige Antwortdaten
- Aufzeichnungskapazität gleich `0`
- Gerät ohne konfigurierte Server-IP
- Gerät mit und ohne Kommunikationspasswort

## 8. Bekannte Abweichungen des aktuellen Beispiels

Die folgenden Punkte müssen zur Erfüllung dieses Pflichtenhefts noch bereinigt
werden:

1. `DEVICE_PASSWORD` wird zwar gelesen, derzeit aber nicht an
   `AnvizClient` übergeben.
2. `getRecordInfo()` liefert laut aktueller Implementierung keine
   `capacity`, das Beispiel greift jedoch auf `$records['capacity']` zu.
3. Bei einer Kapazität von `0` ist die Auslastungsberechnung nicht gegen eine
   Division durch null abgesichert.
4. Im Fehlerpfad ist das Schließen einer bereits aufgebauten Verbindung nicht
   garantiert.
5. Der Rückgabewert von `connect()` wird nicht ausgewertet.
6. `getDeviceInfo()` fängt Fehler einzelner Teilabfragen intern ab. Fehlende
   Werte erscheinen dadurch als `0` oder leere Zeichenkette, während das
   Beispiel trotzdem Erfolg melden kann.
7. Das Modell wird in `getDeviceInfo()` bereits mit Befehl `0x48` geladen und
   anschließend im Beispiel mit `getDeviceModel()` unnötig erneut abgefragt.
8. Die Ausgabe verwendet `Model` statt der festgelegten deutschen
   Bezeichnung `Modell`.
9. `AnvizClient` setzt den Log-Level im Konstruktor auf `DEBUG`; ohne
   Logdatei werden Protokolldumps mit der fachlichen Ausgabe vermischt.
10. Die Debug-Ausgabe protokolliert vollständige Request-Pakete. Beim
    Authentifizierungsbefehl kann darin das Passwort hexadezimal enthalten
    sein.
11. Kurze Netzwerkantworten werden vom Modell mit Ersatzwerten aufgefüllt und
    sind in der Ausgabe nicht von einer realen Nullkonfiguration zu
    unterscheiden.
12. Der Fehlerpfad fängt nur `Exception`, nicht alle `Throwable`-Fehler ab.
13. Die Umgebungsvariablen des Beispiels (`DEVICE_*`) stimmen nicht mit den in
    `.env.example` und `docs/INSTALLATION.md` genannten `ANVIZ_*`-Variablen
    überein.
14. Für das Beispiel bestehen keine automatisierten Tests; die vorhandene
    Testsuite deckt nur CRC16 ab.

## 9. Validierung gegen den Projektstand

Validierungsstand: 20. September 2026. Die Bewertung beruht auf statischer
Codeprüfung sowie einer erfolgreichen PHP-Syntaxprüfung. Eine Prüfung gegen
ein physisches Gerät war nicht möglich.

| User Story | Status | Nachweis und Abweichung |
|---|---|---|
| US-01 | Teilweise erfüllt | `DEVICE_IP`, `DEVICE_PORT` und `DEVICE_ID` werden gelesen; das Passwort wird nicht übergeben, Eingaben werden nicht validiert und die Dokumentation verwendet andere Variablennamen. |
| US-02 | Teilweise erfüllt | Verbindung und 10-Sekunden-Timeout sind implementiert; der Rückgabewert von `connect()` wird nicht geprüft. Protokoll- und CRC-Prüfung liegen in der SDK-Schicht. |
| US-03 | Teilweise erfüllt | Alle Werte werden ausgegeben; das Modell wird doppelt abgefragt und Teilfehler können als Leerwert erscheinen. |
| US-04 | Teilweise erfüllt | Die drei Kapazitäten werden ausgegeben; fehlgeschlagene Abfragen sind durch die Standardwerte `0` nicht erkennbar. |
| US-05 | Weitgehend erfüllt | Abruf und Formatierung sind vorhanden; eine strikte Validierung der Kalenderwerte fehlt. |
| US-06 | Teilweise erfüllt | Die gewünschten Felder und die bedingte Serveradresse sind vorhanden; kurze Antworten werden als Nullkonfiguration dargestellt. |
| US-07 | Nicht erfüllt | `getRecordInfo()` liefert keinen Schlüssel `capacity`; zusätzlich fehlt der Schutz vor Division durch null. |
| US-08 | Teilweise erfüllt | Erfolgs- und Fehlerausgabe sind vorhanden; im Fehlerfall fehlt eine garantierte Bereinigung und `Throwable` wird nicht vollständig erfasst. |
| US-09 | Teilweise erfüllt | Gliederung und Zahlenformat sind vorhanden; `Model` ist nicht deutsch und Debug-Logs können die Ausgabe vermischen. |
| US-10 | Nicht erfüllt | Teilfehler werden in `getDeviceInfo()` verschluckt, das Modell wird doppelt gelesen und Erfolg kann trotz unvollständiger Daten erscheinen. |
| US-11 | Nicht erfüllt | Vollständige Pakete werden auf zwei Ebenen als Hexdump protokolliert; dadurch kann der Authentifizierungs-Payload offengelegt werden. |
| US-12 | Nicht erfüllt | Für diesen Ablauf existieren keine Tests; nur `tests/Unit/CRC16Test.php` ist vorhanden. Die Entwicklungswerkzeuge sind im aktuellen `vendor`-Verzeichnis nicht installiert. |

### Ausgeführte Prüfungen

- `php -l examples/02_device_info.php`: erfolgreich, keine Syntaxfehler
- PHPUnit: nicht ausführbar, `vendor/bin/phpunit` fehlt
- PHPStan: nicht ausführbar, `vendor/bin/phpstan` fehlt
- PHP_CodeSniffer: nicht ausführbar, `vendor/bin/phpcs` fehlt
- Hardware-Integrationstest: nicht ausgeführt, da kein Testgerät und keine
  Verbindungsdaten bereitstanden

# API Reference

## AnvizClient

Hauptklasse für die Geräte-Kommunikation.

### Constructor

```php
$client = new AnvizClient(
    string $host,           // IP-Adresse
    int $port = 5010,       // Port (Standard: 5010)
    int $deviceId = 1,      // Geräte-ID (Standard: 1)
    int $timeout = 5        // Timeout in Sekunden
);
```

### Connection Methods

#### connect(): bool
Verbindung zum Gerät herstellen.

```php
if ($client->connect()) {
    echo "Verbunden";
}
```

#### disconnect(): void
Verbindung trennen.

```php
$client->disconnect();
```

#### isConnected(): bool
Status der Verbindung prüfen.

```php
if ($client->isConnected()) {
    // Gerät ist verbunden
}
```

#### ping(): bool
Verbindungstest durchführen.

```php
if ($client->ping()) {
    echo "Gerät antwortet";
}
```

### Device Information

#### getDeviceInfo(): DeviceInfo
Geräteinformationen abrufen (Kompatibilitäts-Wrapper für getInfo1).

#### getInfo1(): array
Detaillierte Geräte-Basis-Konfiguration abrufen (Befehl 0x30).
- Firmware-Version
- Passwort
- Lautstärke
- Sprache
- Datumsformat
- etc.

#### getInfo2(): array
Erweiterte Geräte-Parameter abrufen (Befehl 0x32).
- FP-Präzision
- Wiegand-Optionen
- Relais-Modus
- Lock-Delay
- etc.

#### setInfo1(int $pass, int $sleepTime, int $volume, int $language, int $dtFormat, int $attendanceState, int $langSettingFlag): bool
Geräte-Basis-Konfiguration setzen (Befehl 0x31).

#### getAdvancedDeviceInfo(): array
Erweiterte Statistiken (Benutzer, Fingerabdrücke, Karten, Aufzeichnungen) abrufen.

```php
$stats = $client->getAdvancedDeviceInfo();
echo $stats['totalUsers'];
```

#### getDeviceModel(): string
Modellbezeichnung abrufen.

```php
$model = $client->getDeviceModel();
// z.B. "TC550"
```

#### getDeviceSerialNumber(): string
Seriennummer abrufen.

#### setDeviceSerialNumber(string $sn): bool
Seriennummer setzen.

#### getDeviceIdCommand(): int
Geräte-ID vom Gerät abrufen (Command 0x52).

#### setDeviceIdCommand(int $id): bool
Geräte-ID auf dem Gerät setzen (Command 0x53).

#### getNetworkConfig(): NetworkConfig
Netzwerkkonfiguration abrufen.

```php
$network = $client->getNetworkConfig();
echo $network->getIpAddress();
echo $network->getMacAddress();
```

### Date & Time

#### getDateTime(): DateTime
Gerät-Datum/Zeit abrufen.

```php
$dt = $client->getDateTime();
echo $dt->format('Y-m-d H:i:s');
```

#### setDateTime(DateTime $dateTime): bool
Gerät-Datum/Zeit setzen.

```php
$newTime = new DateTime('2024-11-13 22:00:00');
$client->setDateTime($newTime);
```

### Timezone & Bell Schedule

#### getTimezone(): TimezoneConfig
Zeitzoneneinstellungen und Sommerzeit-Regeln abrufen.

#### setTimezone(TimezoneConfig $config): bool
Zeitzoneneinstellungen setzen.

#### getBellSchedule(): BellSchedule
Klingelplan (bis zu 8 Slots) abrufen.

#### setBellSchedule(BellSchedule $schedule): bool
Klingelplan setzen.

### Attendance States

#### getAttendanceStateTable(): array
Tabelle der Anwesenheitsstatus (z.B. "Check-In", "Break") abrufen.

#### setAttendanceStateTable(array $states): bool
Tabelle der Anwesenheitsstatus setzen (max. 16 Einträge).

### Records Management

#### getRecordInfo(): array
Aufzeichnungs-Statistik abrufen.

```php
$info = $client->getRecordInfo();
// Array mit: totalRecords, newRecords, capacity
```

#### downloadRecords(int $count = 0): array
Anwesenheitsaufzeichnungen herunterladen.

```php
$records = $client->downloadRecords();
foreach ($records as $record) {
    echo $record->getUserId();
    echo $record->getTimestamp();
}
```

#### clearNewRecords(): bool
Nur neue Aufzeichnungen löschen.

```php
if ($client->clearNewRecords()) {
    echo "Gelöscht";
}
```

#### clearAllRecords(): bool
**Alle** Aufzeichnungen löschen (Vorsicht!).

```php
if ($client->clearAllRecords()) {
    echo "Alle gelöscht";
}
```

### User Management

#### downloadUsers(int $start = 0, int $count = 0): array
Benutzer herunterladen.

```php
// Alle Benutzer
$all = $client->downloadUsers();

// Benutzer 100-199
$range = $client->downloadUsers(100, 100);
```

#### uploadUser(UserInfo $user): bool
Benutzer hochladen.

```php
$user = new UserInfo();
$user->setUserId(12345);
$user->setName("Max Mustermann");
$user->setCardId(98765);

if ($client->uploadUser($user)) {
    echo "Erfolgreich";
}
```

#### deleteUser(int $userId): bool
Benutzer löschen.

```php
if ($client->deleteUser(12345)) {
    echo "Benutzer gelöscht";
}
```

#### downloadStaffInfo(): array
Mitarbeiter-Informationen abrufen (Befehl 0x72). Liefert detailliertere Daten als `downloadUsers`.

#### uploadStaffInfo(array $staffList): bool
Mitarbeiter-Informationen hochladen (Befehl 0x73).

#### enrollFingerprint(int $userId, int $fingerIndex = 0): bool
Registrierung eines Fingerabdrucks am Gerät starten.

#### enrollCard(int $userId, string $cardNumber): bool
Registrierung einer RFID-Karte am Gerät starten.

#### downloadFingerprint(int $userId, int $fingerIndex): ?string
Fingerabdruck-Template vom Gerät herunterladen.

### Access Control

#### openDoor(): bool
Türöffner-Relais für die konfigurierte Zeit aktivieren.

## Models

### AttendanceRecord

```php
$record->getUserId();           // int
$record->getTimestamp();        // DateTime
$record->getVerificationMethod(); // string (z.B. "Fingerprint")
$record->getBackupCode();       // int
$record->getRecordType();       // int
```

### DeviceInfo

```php
$info->getDeviceId();           // int
$info->getFirmwareVersion();    // string
$info->getSerialNumber();       // string
$info->getUserCapacity();       // int
$info->getFingerprintCapacity(); // int
$info->getRecordCapacity();     // int
```

### UserInfo

```php
$user->getUserId();             // int
$user->getName();               // string
$user->getCardId();             // int
$user->getDepartment();         // int
$user->isAdmin();               // bool

// Setter
$user->setUserId(12345);
$user->setName("Name");
$user->setCardId(98765);
$user->setDepartment(1);
$user->setAdmin(false);
```

### NetworkConfig

```php
$network->getIpAddress();       // string
$network->getSubnetMask();      // string
$network->getGateway();         // string
$network->getServerIp();        // string
$network->getServerPort();      // int
$network->getMacAddress();      // string
```

### TimezoneConfig

```php
$config->getTimezoneOffset();   // int
$config->isDaylightSaving();    // bool
$config->getDstStartMonth();    // int
// ...
```

### BellSchedule

```php
$schedule->getAllSchedules();   // array
$schedule->setSchedule($index, $hour, $minute, ...);
```

## Error Handling

```php
try {
    $client->connect();
    $info = $client->getDeviceInfo();
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
    // Logging, Alert, etc.
}
```

## Logging

```php
use Anviz\SDK\Utils\Logger;

Logger::setLogFile(__DIR__ . '/logs/anviz.log');
Logger::setLogLevel('DEBUG'); // DEBUG, INFO, WARN, ERROR

Logger::debug("Debug-Meldung");
Logger::info("Info-Meldung");
Logger::warn("Warnung");
Logger::error("Fehler");
```

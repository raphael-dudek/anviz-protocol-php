# Anviz Protocol Details

## Nachrichtenformat

### Allgemeines Format

```
Offset  |  Länge  |  Feld        |  Beschreibung
--------|---------|--------------|---------------
0       |  1      |  Preamble    |  Immer 0xA5
1-4     |  4      |  Device ID   |  Big-Endian Binär
5       |  1      |  Command/ACK |  Befehlscode oder Bestätigung
6*      |  1      |  RET         |  **Optional:** Nur wenn ACK >= 0x80 (Antwort)
7-8     |  2      |  Length      |  Datenlänge (Big-Endian)
9-N     |  N      |  Data        |  Payload
N+1-2   |  2      |  CRC16       |  Checksumme (Little-Endian)

*Hinweis: Bei Anfragen (Requests) vom SDK zum Gerät (Command < 0x80) entfällt das RET-Byte. Der Header ist dann 8 Bytes groß. Bei Antworten (Responses) vom Gerät (ACK >= 0x80) ist das RET-Byte vorhanden (Header 9 Bytes).*
```

### Beispiel Request

```
0xA5 0x00 0x00 0x00 0x01 0x7F 0x00 0x00 0x?? 0x??
├─┬─┘ ├─────┬─────┘ ├─────┬─────┘ ├────┬────┘ ├────┬────┘
  │      │              │          │      │
  │      │              │          │      └─ CRC16 (Little-Endian)
  │      │              │          └─ Data Length (2 bytes)
  │      │              └─ Command (Ping)
  │      └─ Device ID (0x00000001)
  └─ Preamble

```

## CRC16 Berechnung

**Polynom:** 0xA001  
**Initial Value:** 0xFFFF  
**Methode:** Lookup-Table basiert

```php
use Anviz\SDK\Utils\CRC16;

$payload = [0xA5, 0x00, 0x00, 0x00, 0x01, 0x7F];
$crc = CRC16::compute($payload, count($payload));
// $crc = calculated value

// Validierung
$isValid = CRC16::validate($payloadWithCrc, $totalLength);
```

## Befehlscodes

### Standard Commands

| Code | Beschreibung | Request Data | Response Data |
|------|--------------|--------------|---------------|
| 0x30 | Get Info 1 | - | Device Info |
| 0x31 | Set Info 1 | Config Data | Success |
| 0x32 | Get Info 2 | - | Advanced Info |
| 0x38 | Get DateTime | - | DateTime (6 bytes) |
| 0x39 | Set DateTime | DateTime (6 bytes) | - |
| 0x3A | Get Network | - | Network Config |
| 0x3C | Get Record Info | - | Statistics |
| 0x40 | Download Records | Count (4 bytes) | Records |
| 0x42 | Download Users | Start, Count | Users |
| 0x43 | Upload User | User Data | Success |
| 0x48 | Get Model | - | Model String |
| 0x4C | Delete User | User ID (4 bytes) | Success |
| 0x4E | Clear Records | Flag (1 byte), Amount (2 bytes) | Success |
| 0x70 | Get Attendance States | - | State List |
| 0x71 | Set Attendance States | State List | Success |
| 0x72 | Download Staff Info | Kind, Count | Staff Data |
| 0x73 | Upload Staff Info | Staff Data | Success |
| 0x7F | Ping | - | - |

### DateTime Format

```
Offset  |  Länge  |  Feld
--------|---------|--------
0       |  1      |  YY (0-99, 2000-2099)
1       |  1      |  MM (1-12)
2       |  1      |  DD (1-31)
3       |  1      |  HH (0-23)
4       |  1      |  mm (0-59)
5       |  1      |  ss (0-59)
```

### Record Format (Attendance)

```
Offset  |  Länge  |  Feld
--------|---------|------------------------
0-4     |  5      |  User ID (Binär BE)
5-8     |  4      |  Timestamp (Sekunden seit 2000-01-02, BE)
9       |  1      |  Backup Code
10      |  1      |  Record Type
11-13   |  3      |  Work Type
```

## Verifizierungsmethoden (Backup Codes)

| Code | Methode |
|------|---------|
| 0 | Fingerprint |
| 1 | Password |
| 2 | Card |
| 3 | Fingerprint + Password |
| 4 | Fingerprint + Card |
| 5 | Password + Card |
| 10 | Face |
| 11 | Face + Fingerprint |
| 15 | Palm |

## Multi-Message Handling

Für große Datenmengen werden mehrere Messages gesendet:

```php
// Download 100 Records mit möglicherweise mehreren Responses
$records = [];
$offset = 0;
$batchSize = 50;

while ($offset < 100) {
    $data = BinaryHelper::writeUInt32LE($offset) 
          . BinaryHelper::writeUInt32LE($batchSize);

    $response = $client->sendCommand(0x40, $data);
    $batch = AttendanceRecord::parseMultiple($response->getData());
    $records = array_merge($records, $batch);

    $offset += $batchSize;
}
```

## Error Codes (RET)

| Code | Bedeutung |
|------|-----------|
| 0x00 | Erfolg |
| 0x01 | Fehler |
| 0x02 | Device nicht verfügbar |
| 0x03 | Timeout |
| 0x04 | Datenfehler |

## Netzwerk-Richtlinien

- **Port:** Standard 5010 (TCP)
- **Timeout:** Empfohlen 5-10 Sekunden
- **Verbindungspooling:** Verbindung wiederverwenden für mehrere Operationen
- **Pausen:** 100-500ms zwischen Befehlen für Gerät-Stabilität

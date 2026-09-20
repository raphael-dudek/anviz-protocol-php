# Anviz SDK PHP

Vollständige PHP-Portierung des [MxLabs/Anviz](https://github.com/MxLabs/Anviz) C# SDK für die Kommunikation mit Anviz-Biometric-Geräten über TCP/IP.

## Features

- ✅ Vollständige TCP/IP-Kommunikation
- ✅ CRC16-Validierung nach Anviz-Protokoll
- ✅ Alle gängigen Anviz-Befehle implementiert
- ✅ Geräteinformationen abrufen
- ✅ Datum/Uhrzeit-Verwaltung
- ✅ Anwesenheitsaufzeichnungen herunterladen
- ✅ Benutzerverwaltung (Hinzufügen/Löschen/Abrufen)
- ✅ Fingerabdruck & Gesichts-Template Upload/Download
- ✅ T&A Status-Tabellen Verwaltung
- ✅ Klingelplan & Timezone Konfiguration
- ✅ Türsteuerung & Inquire Card
- ✅ Docker-unterstützt
- ✅ Vollständig dokumentiert mit Beispielen

## Installation

### Lokale Installation

Stellen Sie sicher, dass die PHP-Erweiterung `sockets` in Ihrer `php.ini` aktiviert ist:
`extension=sockets` (Windows) oder `extension=sockets.so` (Linux).

```bash
git clone https://github.com/yourusername/anviz-sdk-php.git
cd anviz-sdk-php
composer install
```

### Docker Installation

```bash
docker-compose up -d anviz-sdk
docker-compose exec anviz-sdk php examples/connect.php
```

## Schnellstart

```php
<?php
require_once 'vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;

// Client erstellen
$client = new AnvizClient('192.168.1.100', 5010, 1, 20, 'dein_passwort');
$client->connect();

// Geräteinformationen
$info = $client->getDeviceInfo();
echo "Modell: " . $client->getDeviceModel() . "\n";
echo "Firmware: " . $info->getFirmwareVersion() . "\n";

// Aufzeichnungen herunterladen
$records = $client->downloadRecords();
foreach ($records as $record) {
    echo sprintf("%s - %s\n",
        $record->getUserId(),
        $record->getTimestamp()->format('Y-m-d H:i:s')
    );
}

$client->disconnect();
```

## Projektstruktur

```
anviz-sdk-php/
├── src/
│   ├── Core/              # Kernkomponenten
│   │   ├── Message.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── AnvizClient.php
│   ├── Communication/     # Netzwerkkommunikation
│   │   └── TcpClient.php
│   ├── Models/            # Datenmodelle
│   │   ├── DeviceInfo.php
│   │   ├── UserInfo.php
│   │   ├── AttendanceRecord.php
│   │   └── NetworkConfig.php
│   └── Utils/             # Hilfsfunktionen
│       ├── CRC16.php
│       ├── BinaryHelper.php
│       └── Logger.php
├── examples/              # Verwendungsbeispiele
│   ├── 01_connect.php
│   ├── 02_device_info.php
│   ├── 03_records.php
│   └── 04_user_management.php
├── tests/                 # Unit Tests
├── docker-compose.yml
├── Dockerfile
├── composer.json
└── README.md
```

## Unterschiede zum offiziellen C# SDK

Diese PHP-Portierung orientiert sich eng am offiziellen Anviz C# SDK, weist jedoch konzeptionelle Unterschiede auf:

### 1. Synchron vs. Asynchron
*   **C# SDK:** Verwendet einen Hintergrund-Thread (`AnvizStream`), der permanent auf Daten wartet. Dies ermöglicht die Verarbeitung von **Push-Events (0xDF)** und automatischen **Keep-Alive Pings (0x7F)** durch das Gerät.
*   **PHP SDK:** Arbeitet rein synchron (Request-Response). Push-Events vom Gerät können während einer aktiven Anfrage den Empfangspuffer stören. Wenn Sie Push-Events benötigen, muss die `TcpClient::receive`-Logik in einer Schleife betrieben werden.

### 2. Datentypen
*   **User-IDs:** Werden in PHP als 64-Bit Integer (bis 5 Bytes genutzt) behandelt.
*   **Passwörter:** Werden im speziellen Anviz-Format (Länge in den oberen 4 Bits) kodiert, um volle Kompatibilität mit Facepass-Geräten zu gewährleisten.

### 3. Paket-Layout (RET-Byte)
Die PHP-Implementierung folgt strikt der Logik, dass das `RET`-Byte nur bei Antwort-Paketen (`ACK >= 0x80`) vorhanden ist, was "Off-by-one"-Fehler beim Lesen der Datenlänge verhindert.

### 4. Protokoll-Treue zum C# SDK
- **Befehlscodes:** Vollständige Harmonisierung der Befehlscodes (z.B. GetDeviceSN=0x24, SetDeviceID=0x47, Enroll=0x5C) mit der offiziellen Referenz.
- **Datenstrukturen:** Implementierung der 27-Byte TCP/IP Parameter-Struktur (0x3A/0x3B), des 30-Slot Klingelplans (0x54/0x55) und der Timezone-Slots (0x50/0x51).
- **Inquire Card:** Unterstützung für das interaktive Einlesen von Karten-IDs (0x7E).
- **Benutzer-IDs:** Konsistente 5-Byte Big-Endian Kodierung für alle benutzerbezogenen Operationen.

## Dokumentation

- [Installation Guide](docs/INSTALLATION.md)
- [API Reference](docs/API.md)
- [Protocol Details](docs/PROTOCOL.md)
- [Examples](examples/)

## Unterstützte Geräte

- TC550, TC500, OC500 Serie
- M7, M5, P7, P5 Serie
- FaceDeep 3/5/7 Serie
- W1, W2, C2 Serie
- Alle Anviz TCP/IP-Geräte

## Lizenz

MIT License

## Credits

- Original C# SDK: [MxLabs/Anviz](https://github.com/MxLabs/Anviz)
- Protokoll: Anviz Global Inc.

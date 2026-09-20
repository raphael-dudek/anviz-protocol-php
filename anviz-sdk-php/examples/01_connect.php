<?php
/**
 * Beispiel 1: Verbindung & Geräteinformationen
 * 
 * Zeigt wie man sich mit einem Anviz-Gerät verbindet und
 * grundlegende Informationen abruft.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;
use Anviz\SDK\Utils\Logger;

// Logging aktivieren
Logger::setLogLevel('DEBUG');
Logger::setLogFile(__DIR__ . '/../logs/anviz.log');

// Konfiguration
$host = getenv('DEVICE_IP') ?: '192.168.2.57';
$port = (int)(getenv('DEVICE_PORT') ?: 5010);
$deviceId = (int)(getenv('DEVICE_ID') ?: 8541);
$password = getenv('DEVICE_PASSWORD') ?: null;

try {
    Logger::info("=== Anviz Device Connection Test ===");

    // Client erstellen
    echo "Verbinde zu $host:$port (ID: $deviceId)...\n";
    $client = new AnvizClient($host, $port, $deviceId, 15, $password);

    // Verbinden
    if (!$client->connect()) {
        echo "✗ Verbindung fehlgeschlagen\n";
        echo "Tipp: Prüfen Sie ob das Gerät die ID $deviceId hat oder versuchen Sie ID 1.\n";
        exit(1);
    }
    echo "✓ Verbunden\n\n";

    // Ping-Test
    echo "Ping-Test... ";
    if ($client->ping()) {
        echo "✓ Gerät antwortet\n\n";
    } else {
        echo "✗ Keine Antwort\n";
        echo "Versuche Diagnose mit ID 0 (Broadcast)...\n";
        $diagClient = new AnvizClient($host, $port, 0, 5);
        if ($diagClient->connect() && $diagClient->ping()) {
            try {
                $realId = $diagClient->getDeviceIdCommand();
                echo "✓ Erfolg! Das Gerät hat die ID: $realId (Sie verwenden $deviceId)\n";
                echo "Bitte passen Sie DEVICE_ID an.\n\n";
            } catch (Exception $e) {
                echo "✓ Gerät antwortet auf ID 0, aber ID konnte nicht abgefragt werden.\n\n";
            }
        } else {
            echo "✗ Auch ID 0 antwortet nicht.\n\n";
        }
    }

    // Geräteinformationen
    echo "=== Geräteinformationen ===\n";
    $deviceInfo = $client->getDeviceInfo();
    printf("Geräte-ID:          %d\n", $deviceInfo->getDeviceId());
    printf("Firmware Version:   %s\n", $deviceInfo->getFirmwareVersion());
    printf("Seriennummer:       %s\n", $deviceInfo->getSerialNumber());
    printf("Max. Benutzer:      %d\n", $deviceInfo->getUserCapacity());
    printf("Max. Fingerabdr:    %d\n", $deviceInfo->getFingerprintCapacity());
    printf("Max. Datensätze:    %d\n\n", $deviceInfo->getRecordCapacity());

    // Modell
    $model = $client->getDeviceModel();
    echo "Modell: $model\n\n";

    // Gerätedatum/Zeit
    echo "=== Datum & Uhrzeit ===\n";
    $dateTime = $client->getDateTime();
    printf("Geräte-Zeit: %s\n\n", $dateTime->format('Y-m-d H:i:s'));

    // Netzwerkkonfiguration
    echo "=== Netzwerkkonfiguration ===\n";
    $network = $client->getNetworkConfig();
    printf("IP-Adresse:         %s\n", $network->getIpAddress());
    printf("Subnetzmaske:       %s\n", $network->getSubnetMask());
    printf("Gateway:            %s\n", $network->getGateway());
    printf("Server-IP:          %s\n", $network->getServerIp());
    printf("Server-Port:        %d\n", $network->getServerPort());
    printf("MAC-Adresse:        %s\n\n", $network->getMacAddress());

    // Aufzeichnungsstatistik
    echo "=== Aufzeichnungen ===\n";
    $recordInfo = $client->getRecordInfo();
    printf("Gesamt Datensätze:  %d\n", $recordInfo['totalRecords']);
    printf("Neue Datensätze:    %d\n", $recordInfo['newRecords']);
    printf("Kapazität:          %d\n\n", $recordInfo['capacity'] ?? 0);

    // Verbindung trennen
    $client->disconnect();
    echo "✓ Verbindung getrennt\n";
    Logger::info("Test erfolgreich abgeschlossen");

} catch (Exception $e) {
    echo "\n✗ Fehler: " . $e->getMessage() . "\n";
    Logger::error($e->getMessage());
    exit(1);
}

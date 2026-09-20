<?php
/**
 * Diagnose-Skript: Versucht die Geräte-ID via Broadcast (ID 0) abzufragen.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;
use Anviz\SDK\Utils\Logger;

Logger::setLogLevel('DEBUG');

$host = getenv('DEVICE_IP') ?: '192.168.2.57';
$port = (int)(getenv('DEVICE_PORT') ?: 5010);
$deviceId = (int)(getenv('DEVICE_ID') ?: 8541);
$password = getenv('DEVICE_PASSWORD') ?: null;

echo "Diagnose: Versuche Broadcast-Abfrage an $host:$port...\n";

try {
    // Wir nutzen ID 0 für den Client, um zu sehen ob das Gerät antwortet
    $client = new AnvizClient($host, $port, 0, 5);
    
    if (!$client->connect()) {
        echo "✗ TCP Verbindung fehlgeschlagen.\n";
        exit(1);
    }
    echo "✓ TCP Verbunden.\n";

    echo "Sende Ping (Broadcast ID 0)...\n";
    if ($client->ping()) {
        echo "✓ Gerät antwortet auf Broadcast Ping!\n";
    } else {
        echo "✗ Gerät antwortet nicht auf Broadcast Ping.\n";
    }

    echo "\nSende Get Device ID (0x46) via Broadcast...\n";
    try {
        $id = $client->getDeviceIdCommand();
        echo "✓ Gerät meldet ID: $id\n";
    } catch (Exception $e) {
        echo "✗ Fehler beim Abrufen der ID: " . $e->getMessage() . "\n";
    }

    $client->disconnect();
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}

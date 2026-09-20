<?php
require_once __DIR__ . '/vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;
use Anviz\SDK\Utils\Logger;

Logger::setLogLevel('DEBUG');

$host = '190.170.150.89';
$port = 8010;
$password = '0'; // Versuche Standard-Passwort

echo "Versuche Diagnose mit ID 0 (Broadcast)...\n";
$client = new AnvizClient($host, $port, 0, 10, $password);

try {
    if ($client->connect()) {
        echo "TCP Verbunden.\n";
        echo "Sende Ping mit ID 0...\n";
        if ($client->ping()) {
            echo "Ping erfolgreich!\n";
        } else {
            echo "Ping fehlgeschlagen.\n";
        }
        
        echo "Versuche Geräte-ID abzufragen...\n";
        $id = $client->getDeviceIdCommand();
        echo "Ermittelte Geräte-ID: $id\n";
    } else {
        echo "TCP Verbindung fehlgeschlagen.\n";
    }
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
} finally {
    $client->disconnect();
}

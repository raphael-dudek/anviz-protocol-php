<?php
/**
 * Beispiel 2: Umfassende Geräteinformationen
 * 
 * Zeigt wie man detaillierte Informationen vom Gerät abruft
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;

$host = getenv('DEVICE_IP') ?: '192.168.2.57';
$port = (int)(getenv('DEVICE_PORT') ?: 5010);
$deviceId = (int)(getenv('DEVICE_ID') ?: 8541);
$password = getenv('DEVICE_PASSWORD') ?: null;

try {
    $client = new AnvizClient($host, $port, $deviceId,10);
    $client->connect();

    echo "=== ANVIZ GERÄTEINFORMATIONEN ===\n";
    echo str_repeat("=", 50) . "\n\n";

    // Basis-Informationen
    echo "[GERÄTEKONFIGURATION]\n";
    $info = $client->getDeviceInfo();
    echo sprintf("  ID:                    %d\n", $info->getDeviceId());
    echo sprintf("  Model:                 %s\n", $client->getDeviceModel());
    echo sprintf("  Firmware:              %s\n", $info->getFirmwareVersion());
    echo sprintf("  Seriennummer:          %s\n", $info->getSerialNumber());

    // Kapazitäten
    echo "\n[KAPAZITÄTEN]\n";
    echo sprintf("  Benutzer:              %d\n", $info->getUserCapacity());
    echo sprintf("  Fingerabdrücke:        %d\n", $info->getFingerprintCapacity());
    echo sprintf("  Aufzeichnungen:        %d\n", $info->getRecordCapacity());

    // Datum & Zeit
    echo "\n[SYSTEM ZEIT]\n";
    $dateTime = $client->getDateTime();
    echo sprintf("  Aktuelle Geräte-Zeit:  %s\n", $dateTime->format('d.m.Y H:i:s'));
    echo sprintf("  Timezone:              " . date_default_timezone_get() . "\n");

    // Netzwerk
    echo "\n[NETZWERK]\n";
    $network = $client->getNetworkConfig();
    echo sprintf("  Hostname:              %s\n", gethostname());
    echo sprintf("  IP-Adresse:            %s\n", $network->getIpAddress());
    echo sprintf("  Netzmaske:             %s\n", $network->getSubnetMask());
    echo sprintf("  Gateway:               %s\n", $network->getGateway());
    echo sprintf("  MAC-Adresse:           %s\n", $network->getMacAddress());

    if ($network->getServerIp() !== '0.0.0.0') {
        echo sprintf("  Server-Adresse:        %s:%d\n", 
            $network->getServerIp(), $network->getServerPort());
    }

    // Aufzeichnungen
    echo "\n[AUFZEICHNUNGEN]\n";
    $records = $client->getRecordInfo();
    echo sprintf("  Gesamt:                %d\n", $records['totalRecords']);
    echo sprintf("  Neue:                  %d\n", $records['newRecords']);
    echo sprintf("  Kapazität:             %d\n", $records['capacity']);

    $used = ($records['totalRecords'] / $records['capacity']) * 100;
    echo sprintf("  Auslastung:            %.1f%%\n", $used);

    echo "\n" . str_repeat("=", 50) . "\n";

    $client->disconnect();
    echo "✓ Erfolgreich\n";

} catch (Exception $e) {
    echo "✗ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

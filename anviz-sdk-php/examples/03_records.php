<?php
/**
 * Beispiel 3: Anwesenheitsaufzeichnungen
 * 
 * Zeigt wie man Aufzeichnungen herunterlädt und verarbeitet
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;

$host = getenv('DEVICE_IP') ?: '192.168.2.57';
$port = (int)(getenv('DEVICE_PORT') ?: 5010);
$deviceId = (int)(getenv('DEVICE_ID') ?: 8541);
$password = getenv('DEVICE_PASSWORD') ?: null;

try {
    $client = new AnvizClient($host, $port, $deviceId);
    $client->connect();

    echo "=== ANWESENHEITSAUFZEICHNUNGEN ===\n";
    echo str_repeat("=", 80) . "\n\n";

    $stateNames = [];
    try {
        $stateNames = $client->getAttendanceStateTable();
    } catch (Exception $e) {
        $stateNames = [];
    }
    $fallbackStateNames = [
        0 => 'Kommen',
        1 => 'Gehen',
        2 => 'Pause Beginn',
        3 => 'Pause Ende',
    ];
    $resolvedStateNames = $fallbackStateNames;
    foreach ($stateNames as $idx => $name) {
        if ($name !== '') {
            $resolvedStateNames[$idx] = $name;
        }
    }

    // Info abrufen
    $info = $client->getRecordInfo();
    echo "Neue Aufzeichnungen: " . $info['newRecords'] . "\n\n";
    echo "Gesamt verfügbar:    " . $info['totalRecords'] . "\n\n";

    $printRecords = function (string $title, array $records) use ($resolvedStateNames): void {
        echo $title . "\n";
        echo "Gefundene Datensätze: " . count($records) . "\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-5s | %-10s | %-20s | %-30s | %-16s\n",
            "Nr.", "User-ID", "Zeitstempel", "Verifizierungsmethode", "Typ");
        echo str_repeat("-", 80) . "\n";
        foreach ($records as $i => $record) {
            printf("%-5d | %-10s | %-20s | %-30s | %-16s\n",
                $i + 1,
                $record->getUserId(),
                $record->getTimestamp()->format('Y-m-d H:i:s'),
                $record->getVerificationMethod(),
                $record->getRecordTypeName($resolvedStateNames)
            );
        }
        echo str_repeat("-", 80) . "\n\n";
    };

    // Neue Aufzeichnungen herunterladen
    echo "Laden (neu)...\n";
    $newRecords = [];
    if ($info['newRecords'] === 0) {
        echo "Keine neuen Aufzeichnungen verfügbar.\n\n";
    } else {
        $newRecords = $client->downloadRecords(0, true);
        $printRecords("=== NEUE AUFZEICHNUNGEN ===", $newRecords);
    }

    // Alle Aufzeichnungen herunterladen
    echo "Laden (gesamt)...\n";
    $allRecords = $client->downloadRecords();
    $printRecords("=== GESAMT AUFZEICHNUNGEN ===", $allRecords);

    // In CSV exportieren (optional)
    $filename = __DIR__ . '/../exports/records_' . date('Y-m-d_H-i-s') . '.csv';
    @mkdir(dirname($filename), 0777, true);

    $fp = fopen($filename, 'w');
    fputcsv($fp, ['Nr.', 'User-ID', 'Zeitstempel', 'Methode', 'Typ']);

    foreach ($allRecords as $i => $record) {
        fputcsv($fp, [
            $i + 1,
            $record->getUserId(),
            $record->getTimestamp()->format('Y-m-d H:i:s'),
            $record->getVerificationMethod(),
            $record->getRecordTypeName($resolvedStateNames)
        ]);
    }

    fclose($fp);
    echo "✓ Exportiert nach: $filename\n\n";

    // Nach Download löschen (optional)
    echo "Neue Aufzeichnungen löschen? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $answer = trim(fgets($handle));

    if ($answer === 'y') {
        if ($client->clearNewRecords()) {
            echo "✓ Aufzeichnungen gelöscht\n";
        } else {
            echo "✗ Fehler beim Löschen\n";
        }
    }

    $client->disconnect();

} catch (Exception $e) {
    echo "✗ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

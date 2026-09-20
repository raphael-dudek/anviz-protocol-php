<?php
/**
 * Beispiel 4: Benutzerverwaltung
 * 
 * Zeigt Benutzer hinzufügen, anzeigen und löschen
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;
use Anviz\SDK\Models\UserInfo;

$host = getenv('DEVICE_IP') ?: '192.168.2.57';
$port = (int)(getenv('DEVICE_PORT') ?: 5010);
$deviceId = (int)(getenv('DEVICE_ID') ?: 8541);
$password = getenv('DEVICE_PASSWORD') ?: null;

try {
    $client = new AnvizClient($host, $port, $deviceId);
    $client->connect();

    echo "=== BENUTZERVERWALTUNG ===\n";
    echo str_repeat("=", 80) . "\n\n";

    echo "Optionen:\n";
    echo "  1 - Benutzer auflisten\n";
    echo "  2 - Benutzer hinzufügen\n";
    echo "  3 - Benutzer löschen\n\n";

    echo "Wählen Sie eine Option (1-3): ";
    $handle = fopen("php://stdin", "r");
    $option = trim(fgets($handle));

    switch ($option) {
        case '1':
            // Benutzer auflisten
            echo "\nLade Benutzer...\n\n";
            $users = $client->downloadAllUsers();
            $staff = [];
            if (empty($users)) {
                try {
                    $staff = $client->downloadStaffInfoAll();
                } catch (Exception $e) {
                    $staff = [];
                }
            }

            $totalFound = count($users) + count($staff);
            echo "Gefundene Benutzer: " . $totalFound . "\n";
            if ($totalFound === 0) {
                echo "Keine Benutzer gefunden.\n";
                break;
            }
            echo str_repeat("-", 80) . "\n";

            printf("%-10s | %-20s | %-15s | %-5s\n", 
                "User-ID", "Name", "Karten-ID", "Admin");
            echo str_repeat("-", 80) . "\n";

            foreach ($users as $user) {
                printf("%-10s | %-20s | %-15s | %-5s\n",
                    $user->getUserId(),
                    $user->getName(),
                    $user->getCardId(),
                    $user->isAdmin() ? 'Ja' : 'Nein'
                );
            }
            foreach ($staff as $entry) {
                printf("%-10s | %-20s | %-15s | %-5s\n",
                    $entry['user_id'] ?? '',
                    $entry['name'] ?? '',
                    $entry['card_id'] ?? '',
                    ($entry['keep'] ?? 0) === 2 ? 'Ja' : 'Nein'
                );
            }
            break;

        case '2':
            // Benutzer hinzufügen
            echo "\n=== Neuen Benutzer erstellen ===\n";

            echo "User-ID: ";
            $userId = (int)trim(fgets($handle));

            echo "Name (max. 10 Zeichen): ";
            $name = trim(fgets($handle));

            echo "Karten-ID (optional): ";
            $cardId = (int)trim(fgets($handle));

            $user = new UserInfo();
            $user->setUserId($userId);
            $user->setName($name);
            if ($cardId > 0) {
                $user->setCardId($cardId);
            }
            $user->setDepartment(0);
            $user->setAdmin(false);

            $staffEntry = [
                'user_id' => $user->getUserId(),
                'pwd' => 'FFFFFF',
                'card_id' => $user->getCardId(),
                'name' => $user->getName(),
                'department' => $user->getDepartment(),
                'group' => $user->getGroup(),
                'mode' => $user->getMode(),
                'pwd_8_digit' => $user->getPwdh8(),
                'keep' => $user->getKeep(),
                'special_info' => $user->getMessage(),
            ];
            $created = $client->uploadStaffInfo([$staffEntry]);
            if (!$created) {
                try {
                    $created = $client->uploadUser($user);
                } catch (Exception $e) {
                    $created = false;
                }
            }

            if ($created) {
                echo "\n✓ Benutzer erfolgreich erstellt\n";
            } else {
                echo "\n✗ Fehler beim Erstellen\n";
            }
            break;

        case '3':
            // Benutzer löschen
            echo "\n=== Benutzer löschen ===\n";

            echo "User-ID zum Löschen: ";
            $userId = (int)trim(fgets($handle));

            echo "Wirklich löschen? (y/n): ";
            $confirm = trim(fgets($handle));

            if ($confirm === 'y') {
                $deleted = false;
                try {
                    $deleted = $client->deleteEmployeeData($userId, 0xFF);
                } catch (Exception $e) {
                    $deleted = false;
                }
                if (!$deleted) {
                    $deleted = $client->deleteUser($userId);
                }

                if ($deleted) {
                    echo "\n✓ Benutzer gelöscht\n";
                } else {
                    echo "\n✗ Fehler beim Löschen\n";
                }
            }
            break;

        default:
            echo "Ungültige Option\n";
    }

    $client->disconnect();

} catch (Exception $e) {
    echo "✗ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

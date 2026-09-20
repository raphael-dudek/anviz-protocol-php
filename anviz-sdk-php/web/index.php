<?php
/**
 * Web Dashboard für Anviz SDK
 * 
 * Einfaches Web-Interface zum Steuern des Geräts
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Anviz\SDK\Core\AnvizClient;

$host = getenv('DEVICE_IP') ?: '192.168.1.100';
$port = (int)(getenv('DEVICE_PORT') ?: 5010);
$deviceId = (int)(getenv('DEVICE_ID') ?: 1);

$action = $_GET['action'] ?? 'info';
$mode = $_GET['mode'] ?? null;
$result = null;
$error = null;

try {
    $client = new AnvizClient($host, $port, $deviceId);
    $client->connect();

    switch ($action) {
        case 'info':
            $info = $client->getDeviceInfo();
            $result = [
                'model' => $client->getDeviceModel(),
                'firmware' => $info->getFirmwareVersion(),
                'serial' => $info->getSerialNumber(),
                'users' => $info->getUserCapacity(),
                'records' => $info->getRecordCapacity()
            ];
            break;

        case 'records':
            $recordInfo = $client->getRecordInfo();
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
            $newRecords = [];
            if ($recordInfo['newRecords'] > 0) {
                $newRecords = $client->downloadRecords(0, true);
            }
            $allRecords = $client->downloadRecords();
            $result = [
                'total' => $recordInfo['totalRecords'],
                'new' => $recordInfo['newRecords'],
                'new_records' => array_map(function($r) use ($resolvedStateNames) {
                    return [
                        'user' => $r->getUserId(),
                        'time' => $r->getTimestamp()->format('Y-m-d H:i:s'),
                        'method' => $r->getVerificationMethod(),
                        'type' => $r->getRecordTypeName($resolvedStateNames)
                    ];
                }, $newRecords),
                'all_records' => array_map(function($r) use ($resolvedStateNames) {
                    return [
                        'user' => $r->getUserId(),
                        'time' => $r->getTimestamp()->format('Y-m-d H:i:s'),
                        'method' => $r->getVerificationMethod(),
                        'type' => $r->getRecordTypeName($resolvedStateNames)
                    ];
                }, $allRecords)
            ];
            break;

        case 'ping':
            if ($client->ping()) {
                $result = "Ping erfolgreich! Gerät antwortet.";
            } else {
                $result = "Ping fehlgeschlagen – Gerät antwortet nicht.";
            }
            break;

        case 'users':
            $users = $client->downloadAllUsers();
            $staff = [];
            try {
                $staff = $client->downloadStaffInfoAll();
            } catch (Exception $e) {
                $staff = [];
            }
            $userIds = [];
            foreach ($users as $u) {
                $userIds[(string)$u->getUserId()] = true;
            }
            if (!empty($staff)) {
                $staff = array_values(array_filter($staff, function ($entry) use ($userIds) {
                    $id = isset($entry['user_id']) ? (string)$entry['user_id'] : '';
                    if ($id === '') {
                        return true;
                    }
                    return !isset($userIds[$id]);
                }));
            }
            if ($mode === 'add') {
                $userId = (int)($_GET['user_id'] ?? 0);
                $name = (string)($_GET['name'] ?? '');
                if ($name !== '' && !preg_match('//u', $name)) {
                    $name = utf8_encode($name);
                }
                $cardId = (int)($_GET['card_id'] ?? 0);
                if ($userId > 0 && $name !== '') {
                    $user = new \Anviz\SDK\Models\UserInfo();
                    $user->setUserId($userId);
                    $user->setName(substr($name, 0, 20));
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

                    $result = $created ? 'Benutzer erstellt' : 'Benutzer konnte nicht erstellt werden';
                } else {
                    $result = 'user_id und name sind erforderlich';
                }
            } elseif ($mode === 'delete') {
                $userId = (int)($_GET['user_id'] ?? 0);
                if ($userId > 0) {
                    $deleted = false;
                    try {
                        $deleted = $client->deleteEmployeeData($userId, 0xFF);
                    } catch (Exception $e) {
                        $deleted = false;
                    }
                    if (!$deleted) {
                        $deleted = $client->deleteUser($userId);
                    }
                    $result = $deleted ? 'Benutzer gelöscht' : 'Benutzer konnte nicht gelöscht werden';
                } else {
                    $result = 'user_id ist erforderlich';
                }
            } else {
                $result = [
                    'users' => $users,
                    'staff' => $staff
                ];
            }
            break;

        case 'status':
            header('Content-Type: application/json');
            echo json_encode(['status' => 'online', 'device' => $host]);
            exit;

    }

    $client->disconnect();

} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anviz SDK Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        header { background: #2c3e50; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        nav { margin-bottom: 20px; }
        nav a { display: inline-block; margin-right: 10px; padding: 10px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 3px; }
        nav a:hover { background: #2980b9; }
        .card { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h2 { margin-bottom: 15px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; font-weight: bold; }
        .error { background: #e74c3c; color: white; padding: 15px; border-radius: 3px; margin-bottom: 20px; }
        .success { background: #27ae60; color: white; padding: 15px; border-radius: 3px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Anviz SDK Dashboard</h1>
            <p>Gerät: <?php echo $host; ?>:<?php echo $port; ?></p>
        </header>

        <?php if ($error): ?>
            <div class="error">Fehler: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <nav>
            <a href="?action=info" <?php echo $action === 'info' ? 'style="background: #2980b9;"' : ''; ?>>Geräteinformation</a>
            <a href="?action=records" <?php echo $action === 'records' ? 'style="background: #2980b9;"' : ''; ?>>Aufzeichnungen</a>
            <a href="?action=users" <?php echo $action === 'users' ? 'style="background: #2980b9;"' : ''; ?>>Benutzer</a>
            <a href="?action=ping" <?php echo $action === 'ping' ? 'style="background: #2980b9;"' : ''; ?>>Ping</a>
        </nav>

        <?php if ($result): ?>
            <div class="card">
                <?php if ($action === 'info'): ?>
                    <h2>Geräteinformationen</h2>
                    <table>
                        <tr><th>Eigenschaft</th><th>Wert</th></tr>
                        <tr><td>Modell</td><td><?php echo htmlspecialchars($result['model']); ?></td></tr>
                        <tr><td>Firmware</td><td><?php echo htmlspecialchars($result['firmware']); ?></td></tr>
                        <tr><td>Seriennummer</td><td><?php echo htmlspecialchars($result['serial']); ?></td></tr>
                        <tr><td>Max. Benutzer</td><td><?php echo $result['users']; ?></td></tr>
                        <tr><td>Max. Aufzeichnungen</td><td><?php echo $result['records']; ?></td></tr>
                    </table>
                <?php elseif ($action === 'records'): ?>
                    <h2>Anwesenheitsaufzeichnungen</h2>
                    <p>Gesamt: <?php echo $result['total']; ?> | Neue: <?php echo $result['new']; ?></p>
                    <?php if (!empty($result['new_records'])): ?>
                        <h3>Neue Aufzeichnungen</h3>
                        <table>
                            <tr><th>User-ID</th><th>Zeitstempel</th><th>Methode</th><th>Typ</th></tr>
                            <?php foreach ($result['new_records'] as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['user']); ?></td>
                                    <td><?php echo htmlspecialchars($r['time']); ?></td>
                                    <td><?php echo htmlspecialchars($r['method']); ?></td>
                                    <td><?php echo htmlspecialchars($r['type']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>Keine neuen Aufzeichnungen vorhanden</p>
                    <?php endif; ?>

                    <?php if (!empty($result['all_records'])): ?>
                        <h3>Gesamt Aufzeichnungen</h3>
                        <table>
                            <tr><th>User-ID</th><th>Zeitstempel</th><th>Methode</th><th>Typ</th></tr>
                            <?php foreach ($result['all_records'] as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['user']); ?></td>
                                    <td><?php echo htmlspecialchars($r['time']); ?></td>
                                    <td><?php echo htmlspecialchars($r['method']); ?></td>
                                    <td><?php echo htmlspecialchars($r['type']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p>Keine Aufzeichnungen vorhanden</p>
                    <?php endif; ?>
                <?php elseif ($action === 'ping'): ?>
                    <h2>Ping-Test</h2>
                    <p>Ergebnis: <?php echo $result; ?></p>
                <?php elseif ($action === 'users'): ?>
                    <h2>Benutzerverwaltung</h2>
                    <?php if (is_string($result)): ?>
                        <p><?php echo htmlspecialchars($result); ?></p>
                    <?php else: ?>
                        <?php
                            $users = $result['users'] ?? [];
                            $staff = $result['staff'] ?? [];
                            $totalFound = count($users) + count($staff);
                        ?>
                        <p>Gefundene Benutzer: <?php echo $totalFound; ?></p>
                        <?php if ($totalFound > 0): ?>
                            <table>
                                <tr><th>User-ID</th><th>Name</th><th>Karten-ID</th><th>Admin</th><th>Aktion</th></tr>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u->getUserId()); ?></td>
                                        <td><?php echo htmlspecialchars($u->getName()); ?></td>
                                        <td><?php echo htmlspecialchars($u->getCardId()); ?></td>
                                        <td><?php echo $u->isAdmin() ? 'Ja' : 'Nein'; ?></td>
                                        <td><a href="?action=users&amp;mode=delete&amp;user_id=<?php echo urlencode($u->getUserId()); ?>">Löschen</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach ($staff as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['user_id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($s['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($s['card_id'] ?? ''); ?></td>
                                        <td><?php echo ($s['keep'] ?? 0) === 2 ? 'Ja' : 'Nein'; ?></td>
                                        <td>
                                            <?php if (!empty($s['user_id'])): ?>
                                                <a href="?action=users&amp;mode=delete&amp;user_id=<?php echo urlencode($s['user_id']); ?>">Löschen</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p>Keine Benutzer gefunden</p>
                        <?php endif; ?>
                        <h3>Benutzer hinzufügen</h3>
                        <form method="get" action="">
                            <input type="hidden" name="action" value="users">
                            <input type="hidden" name="mode" value="add">
                            <table>
                                <tr>
                                    <td><label for="user_id">User-ID</label></td>
                                    <td><input type="number" id="user_id" name="user_id" required></td>
                                </tr>
                                <tr>
                                    <td><label for="name">Name (max. 20 Zeichen)</label></td>
                                    <td><input type="text" id="name" name="name" maxlength="20" required></td>
                                </tr>
                                <tr>
                                    <td><label for="card_id">Karten-ID</label></td>
                                    <td><input type="number" id="card_id" name="card_id"></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td><button type="submit">Benutzer anlegen</button></td>
                                </tr>
                            </table>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

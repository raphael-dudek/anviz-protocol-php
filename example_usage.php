<?php
/**
 * Anviz Protocol PHP Implementation - Usage Examples
 * 
 * @author Reference Implementation
 * @version 1.0.0
 */

require_once __DIR__ . '/AnvizProtocol.php';

use Anviz\AnvizProtocol;

// ============================================================
// Configuration
// ============================================================

$DEVICE_IP = getenv('DEVICE_IP') ?: '192.168.1.100';  // Anviz device IP address
$DEVICE_PORT = getenv('DEVICE_PORT') ?: 5010;         // TCP port (default: 5010)
$DEVICE_ID = getenv('DEVICE_ID') ?: 5;                // Device ID (default: 5)

// ============================================================
// Initialize Connection
// ============================================================

$anviz = new AnvizProtocol($DEVICE_IP, $DEVICE_PORT, $DEVICE_ID);

// Connect to device
if (!$anviz->connect()) {
    die("Failed to connect to Anviz device at $DEVICE_IP:$DEVICE_PORT\n");
}

echo "Connected to Anviz device\n";

// ============================================================
// COMMUNICATION FUNDAMENTALS - Date/Time
// ============================================================

echo "\n=== Date/Time Functions ===\n";

// Get device clock
$clock = $anviz->getDeviceClock();
if ($clock) {
    printf("Device Time: %04d-%02d-%02d %02d:%02d:%02d\n",
        $clock['year'], $clock['month'], $clock['day'],
        $clock['hour'], $clock['minute'], $clock['second']
    );
} else {
    echo "Failed to get device clock\n";
}

// Set device clock
if ($anviz->setDeviceClock(date('Y-m-d H:i:s'))) {
    echo "Device clock set successfully\n";
} else {
    echo "Failed to set device clock\n";
}

// ============================================================
// BASIC DEVICE INFORMATION
// ============================================================

echo "\n=== Basic Device Information ===\n";

// Get device serial number
$sn = $anviz->getDeviceSerialNumber();
if ($sn) {
    echo "Device S/N: $sn\n";
}

// Get device ID
$id = $anviz->getDeviceID();
if ($id !== null) {
    echo "Device ID: $id\n";
}

// Get device configuration
$config = $anviz->getDeviceConfig();
if ($config) {
    echo "Device Config: " . json_encode($config['parsed']) . "\n";
}

// ============================================================
// USER MANAGEMENT
// ============================================================

echo "\n=== User Management ===\n";

// Download staff data
$staffData = $anviz->downloadStaffData();
if ($staffData) {
    echo "Staff Data Downloaded: " . $staffData['total_records'] . " bytes\n";
}

// Delete user (example: user ID 1)
if ($anviz->deleteUser(1)) {
    echo "User 1 deleted successfully\n";
} else {
    echo "Failed to delete user 1\n";
}

// ============================================================
// RECORDS MANAGEMENT
// ============================================================

echo "\n=== Records Management ===\n";

// Get record information
$recordInfo = $anviz->getRecordInfo();
if ($recordInfo) {
    echo "Total Records: " . $recordInfo['total_records'] . "\n";
    echo "New Records: " . $recordInfo['new_records'] . "\n";
}

// Download records
$records = $anviz->downloadRecords();
if ($records) {
    echo "Downloaded " . count($records) . " records\n";
    foreach ($records as $index => $record) {
        if ($index < 5) { // Show first 5 records
            printf("  Record %d: User %d @ %s\n",
                $index + 1,
                $record['user_id'],
                $record['datetime']
            );
        }
    }
}

// Download new records
$newRecords = $anviz->downloadNewRecords();
if ($newRecords) {
    echo "Downloaded " . count($newRecords) . " new records\n";
}

// Clear records
if ($anviz->clearRecords()) {
    echo "Records cleared successfully\n";
}

// ============================================================
// NETWORK CONFIGURATION
// ============================================================

echo "\n=== Network Configuration ===\n";

// Get TCP/IP parameters
$tcpIpParams = $anviz->getTcpIpParams();
if ($tcpIpParams) {
    echo "IP Address: " . $tcpIpParams['ip_address'] . "\n";
    echo "Subnet Mask: " . $tcpIpParams['subnet_mask'] . "\n";
    echo "Gateway: " . $tcpIpParams['gateway'] . "\n";
    echo "Port: " . $tcpIpParams['port'] . "\n";
}

// Set TCP/IP parameters (example - modify as needed)
$networkConfig = [
    'ip_address' => $tcpIpParams['ip_address'], // Neue IP-Adresse
    'subnet_mask' => $tcpIpParams['subnet_mask'], // Subnetzmaske
    'gateway' => $tcpIpParams['gateway'], // Gateway
    'port' => $tcpIpParams['port'] // (Optional) Port
];

if ($anviz->setTcpIpParams($networkConfig)) {
    echo "TCP/IP parameters set successfully\n";
}

// ============================================================
// TIMEZONE INFORMATION
// ============================================================

echo "\n=== Timezone Information ===\n";

// Get timezone
$timezone = $anviz->getTimezone();
if ($timezone) {
    echo "Timezone Offset: " . $timezone['timezone_offset'] . "\n";
    echo "Daylight Saving: " . ($timezone['daylight_saving'] ? "Enabled" : "Disabled") . "\n";
}

// Set timezone (example: UTC+1, no daylight saving)
//if ($anviz->setTimezone(1, false)) {
//    echo "Timezone set successfully\n";
//}

// ============================================================
// DEVICE CONTROL
// ============================================================

echo "\n=== Device Control ===\n";

// Ping device
if ($anviz->ping()) {
    echo "Device is online (Ping successful)\n";
} else {
    echo "Device is offline (Ping failed)\n";
}

// Open door (use with caution!)
// if ($anviz->openDoor()) {
//     echo "Door opened successfully\n";
// }

// Reboot device
// if ($anviz->rebootDevice()) {
//     echo "Device rebooting...\n";
// }

// ============================================================
// Cleanup
// ============================================================

$anviz->disconnect();
echo "\nDisconnected from device\n";

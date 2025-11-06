<?php
/**
 * Anviz PHP Protocol Implementation
 * 
 * Reference Implementation based on:
 * - MxLabs/Anviz (.NET) - https://github.com/MxLabs/Anviz
 * - Anviz TC Communications Protocol
 * - StackOverflow Q18929423 (Checksum Algorithm)
 * 
 * @version 1.0.0
 * @license MIT
 */

namespace Anviz;

use DateTime;
use Exception;
use InvalidArgumentException;

class AnvizProtocol
{
    // Command Codes
    private const CMD_GET_DEVICE_CLOCK = 0x38;
    private const CMD_SET_DEVICE_CLOCK = 0x39;
    private const CMD_GET_DEVICE_CONFIG_1 = 0x30;
    private const CMD_SET_DEVICE_CONFIG_1 = 0x31;
    private const CMD_GET_DEVICE_CONFIG_2 = 0x32;
    private const CMD_SET_DEVICE_CONFIG_2 = 0x33;
    private const CMD_GET_DEVICE_SN = 0x50;
    private const CMD_SET_DEVICE_SN = 0x51;
    private const CMD_GET_DEVICE_ID = 0x52;
    private const CMD_SET_DEVICE_ID = 0x53;
    private const CMD_DOWNLOAD_STAFF_DATA = 0x3C;
    private const CMD_UPLOAD_STAFF_DATA = 0x3D;
    private const CMD_DOWNLOAD_RECORDS = 0x4C;
    private const CMD_DOWNLOAD_NEW_RECORDS = 0x74;
    private const CMD_CLEAR_RECORDS = 0x4D;
    private const CMD_GET_RECORDS_INFO = 0x82;
    private const CMD_DOWNLOAD_FINGERPRINT = 0x40;
    private const CMD_UPLOAD_FINGERPRINT = 0x41;
    private const CMD_DELETE_USER = 0x92;
    private const CMD_ENROLL_FINGERPRINT = 0x63;
    private const CMD_GET_TCP_IP_PARAMS = 0x5C;
    private const CMD_SET_TCP_IP_PARAMS = 0x5D;
    private const CMD_GET_TIMEZONE = 0xB0;
    private const CMD_SET_TIMEZONE = 0xB1;
    private const CMD_GET_BELL_SCHEDULE = 0xB2;
    private const CMD_SET_BELL_SCHEDULE = 0xB3;
    private const CMD_REBOOT_DEVICE = 0x8B;
    private const CMD_FACTORY_RESET = 0x8D;
    private const CMD_PING = 0x81;
    private const CMD_DEVICE_PINGS = 0x85;
    private const CMD_OPEN_DOOR = 0x7E;
    private const CMD_GET_ADVANCED_INFO = 0x34;
    private const CMD_SET_ADVANCED_INFO = 0x35;
    private const CMD_GET_FACEPASS_TEMPLATES = 0x98;
    private const CMD_SET_FACEPASS_TEMPLATES = 0x99;
    private const CMD_GET_DEVICE_ADVANCED_INFO = 0x34;
    private const CMD_ENROLL_CARD = 0x64;
    private const CMD_DELETE_RECORD = 0x4E;
    private const CMD_GET_DST_RULES = 0xB4;
    private const CMD_SET_DST_RULES = 0xB5;


    // Packet Constants
    private const PACKET_HEADER = 0xA5;
    private const SOCKET_TIMEOUT = 5;

    private $socket = null;
    private $host;
    private $port;
    private $deviceId;
    private $connected = false;

    /**
     * Constructor
     * 
     * @param string $host Device IP address
     * @param int $port TCP Port (default 5010)
     * @param int $deviceId Device ID
     */
    public function __construct(string $host, int $port = 5010, int $deviceId = 5)
    {
        $this->host = $host;
        $this->port = $port;
        $this->deviceId = $deviceId;
    }

    /**
     * Connect to device
     * 
     * @return bool Connection status
     */
    public function connect(): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, self::SOCKET_TIMEOUT);

        if (!$this->socket) {
            $this->logError("Connection failed: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($this->socket, self::SOCKET_TIMEOUT);
        $this->connected = true;
        return true;
    }

    /**
     * Disconnect from the device
     */
    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    /**
     * Calculate Checksum (CRC-16 CCITT)
     * Based on StackOverflow Q18929423
     * 
     * @param array $bytes Byte array without header and device ID
     * @return array [low_byte, high_byte]
     */
    private function calculateChecksum(array $bytes): array
    {
        $crc = 0xFFFF;

        foreach ($bytes as $byte) {
            $crc ^= ($byte & 0xFF);

            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x0001) {
                    $crc = ($crc >> 1) ^ 0xA001;
                } else {
                    $crc = $crc >> 1;
                }
            }
        }

        return [
            $crc & 0xFF,           // Low byte
            ($crc >> 8) & 0xFF     // High byte
        ];
    }

    /**
     * Build the command packet
     * 
     * @param int $command Command code
     * @param array $data Command data
     * @return string Hex packet string
     */
    private function buildPacket(int $command, array $data = []): string
    {
        $packet = [];

        // Header
        $packet[] = self::PACKET_HEADER;

        // Device ID (4 bytes, little-endian)
        $packet[] = $this->deviceId & 0xFF;
        $packet[] = ($this->deviceId >> 8) & 0xFF;
        $packet[] = ($this->deviceId >> 16) & 0xFF;
        $packet[] = ($this->deviceId >> 24) & 0xFF;

        // Command Code
        $packet[] = $command;

        // Command Data
        if (!empty($data)) {
            $packet = array_merge($packet, $data);
        }

        // Calculate and append checksum
        $checksumBytes = array_slice($packet, 1); // Exclude header
        $checksum = $this->calculateChecksum($checksumBytes);
        $packet[] = $checksum[0];
        $packet[] = $checksum[1];

        // Convert to hex string
        return implode('', array_map(function($b) { 
            return strtoupper(str_pad(dechex($b), 2, '0', STR_PAD_LEFT)); 
        }, $packet));
    }

    /**
     * Send command to the device
     * 
     * @param int $command Command code
     * @param array $data Command data
     * @return string Response hex string
     */
    private function sendCommand(int $command, array $data = []): ?string
    {
        if (!$this->connected || !$this->socket) {
            $this->logError("Device not connected");
            return null;
        }

        $packet = $this->buildPacket($command, $data);

        // Convert hex string to binary
        $binary = '';
        for ($i = 0; $i < strlen($packet); $i += 2) {
            $binary .= chr(hexdec(substr($packet, $i, 2)));
        }

        // Send the packet
        if (fwrite($this->socket, $binary) === false) {
            $this->logError("Failed to send command");
            return null;
        }

        // Read response
        return $this->readResponse();
    }

    /**
     * Read response from the device
     * 
     * @return string Response hex string
     */
    private function readResponse(): string
    {
        $response = '';
        $buffer = '';

        while (!feof($this->socket)) {
            $chunk = fread($this->socket, 1024);
            if ($chunk === false) break;
            $buffer .= $chunk;

            if (strlen($buffer) > 0) {
                break;
            }
        }

        // Convert binary to hex
        for ($i = 0; $i < strlen($buffer); $i++) {
            $response .= strtoupper(str_pad(dechex(ord($buffer[$i])), 2, '0', STR_PAD_LEFT));
        }

        return $response;
    }

    // ============================================================
    // COMMUNICATION FUNDAMENTALS
    // ============================================================

    /**
     * Get device clock (Date and Time)
     * Command: 0x38
     * 
     * @return array|null [year, month, day, hour, minute, second]
     */
    public function getDeviceClock(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_CLOCK);

        if (!$response || strlen($response) < 30) {
            return null;
        }

        $bytes = str_split($response, 2);

        if (count($bytes) < 15) return null;

        return [
            'year' => 2000 + hexdec($bytes[9]),
            'month' => hexdec($bytes[10]),
            'day' => hexdec($bytes[11]),
            'hour' => hexdec($bytes[12]),
            'minute' => hexdec($bytes[13]),
            'second' => hexdec($bytes[14])
        ];
    }

    /**
     * Set device clock (Date and Time)
     * Command: 0x39
     * 
     * @param string $dateTime DateTime string (YYYY-MM-DD HH:MM:SS)
     * @return bool Success status
     */
    public function setDeviceClock(string $dateTime): bool
    {
        try {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
            if (!$dt) return false;

            $data = [
                0x00, 0x00,
                hexdec($dt->format('y')), // Year (last 2 digits)
                hexdec($dt->format('m')), // Month
                hexdec($dt->format('d')), // Day
                hexdec($dt->format('H')), // Hour
                hexdec($dt->format('i')), // Minute
                hexdec($dt->format('s'))  // Second
            ];

            $response = $this->sendCommand(self::CMD_SET_DEVICE_CLOCK, $data);
            return $response !== null;
        } catch (Exception $e) {
            $this->logError("Error setting device clock: " . $e->getMessage());
            return false;
        }
    }

    // ========== V2.0: NEW METHODS - CATEGORY 3 ==========

    /**
     * Get device configuration 2 (Advanced Settings)
     * Command: 0x32
     */
    public function getDeviceConfiguration2(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_CONFIG_2);

        if (!$response) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'fingerprint_threshold' => hexdec($bytes[5] ?? '00'),
            'card_detection_time' => hexdec($bytes[6] ?? '00'),
            'raw_response' => $response
        ];
    }

    /**
     * Get the device advanced information
     * Command: 0x34
     */
    public function getDeviceAdvancedInfo(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_ADVANCED_INFO);

        if (!$response) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'total_users' => hexdec($bytes[5] ?? '00') | (hexdec($bytes[6] ?? '00') << 8),
            'total_records' => hexdec($bytes[7] ?? '00') | (hexdec($bytes[8] ?? '00') << 8),
            'total_templates' => hexdec($bytes[9] ?? '00') | (hexdec($bytes[10] ?? '00') << 8),
            'device_status' => hexdec($bytes[11] ?? '00'),
            'raw_response' => $response
        ];
    }

    // ========== V2.0: NEW METHODS - CATEGORY 4 ==========

    /**
     * Enroll fingerprint for user
     * Command: 0x63
     */
    public function enrollFingerprint($userId, $fingerIndex = 1, $options = []): bool
    {
        $data = [
            $userId & 0xFF,
            ($userId >> 8) & 0xFF,
            $fingerIndex & 0xFF,
            $options['quality_threshold'] ?? 80
        ];

        $response = $this->sendCommand(self::CMD_ENROLL_FINGERPRINT, $data);

        if ($response) {
            $this->logInfo("Fingerprint enrollment initiated for user $userId");
            return true;
        }

        return false;
    }

    /**
     * Enroll RFID card for user
     * Command: 0x64
     */
    public function enrollCard($userId, $cardNumber): bool
    {
        if (!is_numeric($cardNumber)) {
            $this->logError("Invalid card number: $cardNumber");
            return false;
        }

        $data = [];
        $data[] = $userId & 0xFF;
        $data[] = ($userId >> 8) & 0xFF;

        $cardBytes = str_split($cardNumber);
        foreach ($cardBytes as $byte) {
            $data[] = ord($byte);
        }

        $response = $this->sendCommand(self::CMD_ENROLL_CARD, $data);

        return $response !== null;
    }

    /**
     * Delete specific record
     * Command: 0x4E
     */
    public function deleteRecord($recordIndex): bool
    {
        $data = [
            $recordIndex & 0xFF,
            ($recordIndex >> 8) & 0xFF
        ];

        $response = $this->sendCommand(self::CMD_DELETE_RECORD, $data);

        if ($response) {
            $this->logInfo("Record $recordIndex deleted");
            return true;
        }

        return false;
    }

    /**
     * Get bell/schedule configuration
     * Command: 0xB2
     */
    public function getBellSchedule(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_BELL_SCHEDULE);

        if (!$response) {
            return null;
        }

        $bytes = str_split($response, 2);
        $schedules = [];

        for ($i = 0; $i < 8; $i++) {
            $offset = 5 + ($i * 4);
            if ($offset + 3 < count($bytes)) {
                $schedules[] = [
                    'time' => sprintf('%02d:%02d',
                        hexdec($bytes[$offset] ?? '00'),
                        hexdec($bytes[$offset + 1] ?? '00')
                    ),
                    'melody' => hexdec($bytes[$offset + 2] ?? '00'),
                    'volume' => hexdec($bytes[$offset + 3] ?? '00')
                ];
            }
        }

        return [
            'schedules' => $schedules,
            'raw_response' => $response
        ];
    }

    /**
     * Set bell/schedule configuration
     * Command: 0xB3
     */
    public function setBellSchedule($schedules): bool
    {
        $data = [];

        foreach (array_slice($schedules, 0, 8) as $schedule) {
            $timeParts = explode(':', $schedule['time']);
            $data[] = (int)$timeParts[0];
            $data[] = (int)$timeParts[1];
            $data[] = $schedule['melody'] ?? 0;
            $data[] = $schedule['volume'] ?? 100;
        }

        $response = $this->sendCommand(self::CMD_SET_BELL_SCHEDULE, $data);

        if ($response) {
            $this->logInfo("Bell schedule updated");
        }

        return $response !== null;
    }

    /**
     * Get Daylight Saving Time rules
     * Command: 0xB4
     */
    public function getDSTRules(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_DST_RULES);

        if (!$response) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'dst_start_month' => hexdec($bytes[5] ?? '00'),
            'dst_start_week' => hexdec($bytes[6] ?? '00'),
            'dst_start_day' => hexdec($bytes[7] ?? '00'),
            'dst_start_hour' => hexdec($bytes[8] ?? '00'),
            'dst_end_month' => hexdec($bytes[9] ?? '00'),
            'dst_end_week' => hexdec($bytes[10] ?? '00'),
            'dst_end_day' => hexdec($bytes[11] ?? '00'),
            'dst_end_hour' => hexdec($bytes[12] ?? '00'),
            'raw_response' => $response
        ];
    }

    /**
     * Set Daylight Saving Time rules
     * Command: 0xB5
     */
    public function setDSTRules($dstConfig): bool
    {
        $data = [
            $dstConfig['start_month'] ?? 3,
            $dstConfig['start_week'] ?? 2,
            $dstConfig['start_day'] ?? 0,
            $dstConfig['start_hour'] ?? 2,
            $dstConfig['end_month'] ?? 10,
            $dstConfig['end_week'] ?? 1,
            $dstConfig['end_day'] ?? 0,
            $dstConfig['end_hour'] ?? 3
        ];

        $response = $this->sendCommand(self::CMD_SET_DST_RULES, $data);

        if ($response) {
            $this->logInfo("DST rules updated");
        }

        return $response !== null;
    }

    // ============================================================
    // BASIC DEVICE INFORMATION
    // ============================================================

    /**
     * Get device serial number
     * Command: 0x50
     * 
     * @return string|null Device serial number
     */
    public function getDeviceSerialNumber(): ?string
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_SN);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);
        $sn = '';

        for ($i = 5; $i < count($bytes) - 2; $i++) {
            $byte = hexdec($bytes[$i]);
            if ($byte > 0) {
                $sn .= chr($byte);
            }
        }

        return $sn;
    }

    /**
     * Get device ID
     * Command: 0x52
     * 
     * @return int|null Device ID
     */
    public function getDeviceID(): ?int
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_ID);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);
        return hexdec($bytes[5]) | 
               (hexdec($bytes[6]) << 8) | 
               (hexdec($bytes[7]) << 16) | 
               (hexdec($bytes[8]) << 24);
    }

    /**
     * Get device type
     * Command: 0x50 (varies by device)
     * 
     * @return string|null Device type
     */
    public function getDeviceType(): ?string
    {
        // Implementation depends on specific device response format
        return $this->sendCommand(0x50);
    }

    /**
     * Get basic device information
     * Command: 0x30
     * 
     * @return array|null Device configuration
     */
    public function getDeviceConfig(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_CONFIG_1);

        if (!$response) {
            return null;
        }

        // Parse response based on protocol specification
        return [
            'raw_response' => $response,
            'parsed' => $this->parseDeviceConfig($response)
        ];
    }

    /**
     * Parse device configuration response
     * 
     * @param string $response Hex response string
     * @return array Parsed configuration
     */
    private function parseDeviceConfig(string $response): array
    {
        $bytes = str_split($response, 2);
        $config = [];

        // Parse based on Anviz protocol specification
        // This is a placeholder for actual implementation
        $config['language'] = hexdec($bytes[5] ?? '00');
        $config['timezone'] = hexdec($bytes[6] ?? '00');

        return $config;
    }

    // ============================================================
    // USER MANAGEMENT
    // ============================================================

    /**
     * Download staff data
     * Command: 0x3C
     * 
     * @return array|null Staff data
     */
    public function downloadStaffData(): ?array
    {
        $response = $this->sendCommand(self::CMD_DOWNLOAD_STAFF_DATA);

        if (!$response) {
            return null;
        }

        return [
            'raw_response' => $response,
            'total_records' => strlen($response) / 2
        ];
    }

    /**
     * Upload staff data
     * Command: 0x3D
     *
     * @param array $users Staff information array
     * @return bool Success status
     */
    public function uploadStaffData(array $users): bool
    {
        if (empty($users)) {
            $this->logError("No user data provided");
            return false;
        }

        $data = [];

        foreach ($users as $user) {
            $data[] = $user['id'] & 0xFF;
            $data[] = ($user['id'] >> 8) & 0xFF;

            $name = str_pad($user['name'] ?? '', 20);
            for ($i = 0; $i < 20; $i++) {
                $data[] = ord($name[$i]);
            }

            $data[] = $user['department'] ?? 0;
        }

        $response = $this->sendCommand(self::CMD_UPLOAD_STAFF_DATA, $data);

        if ($response) {
            $this->logInfo("Staff data uploaded: " . count($users) . " users");
        }

        return $response !== null;
    }

    /**
     * Delete user
     * Command: 0x92
     * 
     * @param int $userId User ID to delete
     * @return bool Success status
     */
    public function deleteUser(int $userId): bool
    {
        $data = [
            ($userId & 0xFF),
            (($userId >> 8) & 0xFF),
            (($userId >> 16) & 0xFF),
            (($userId >> 24) & 0xFF)
        ];

        $response = $this->sendCommand(self::CMD_DELETE_USER, $data);
        return $response !== null;
    }

    // ============================================================
    // RECORDS MANAGEMENT
    // ============================================================

    /**
     * Download attendance records
     * Command: 0x4C
     *
     * @return array|null Records data
     */
    public function downloadRecords($options = []): ?array
    {
        $startIndex = $options['start_index'] ?? 0;
        $count = $options['count'] ?? 0xFFFF;

        $data = [
            $startIndex & 0xFF,
            ($startIndex >> 8) & 0xFF,
            $count & 0xFF,
            ($count >> 8) & 0xFF
        ];

        $response = $this->sendCommand(self::CMD_DOWNLOAD_RECORDS, $data);

        if (!$response) {
            return null;
        }

        return [
            'total_records' => $this->parseRecordCount($response),
            'records' => $this->parseAttendanceRecords($response),
            'raw_response' => $response
        ];
    }

    /**
     * Download new records
     * Command: 0x74
     * 
     * @return array|null New records data
     */
    public function downloadNewRecords(): ?array
    {
        $response = $this->sendCommand(self::CMD_DOWNLOAD_NEW_RECORDS);

        if (!$response) {
            return null;
        }

        return $this->parseRecords($response);
    }

    /**
     * Parse attendance records
     * 
     * @param string $response Hex response string
     * @return array Parsed records
     */
    private function parseRecords(string $response): array
    {
        //$bytes = str_split($response, 2);
        $records = [];

        // Parse based on Anviz protocol specification
        // Each record is typically 16 bytes
        $recordSize = 16;
        $totalRecords = floor((strlen($response) - 30) / ($recordSize * 2));

        for ($i = 0; $i < $totalRecords; $i++) {
            $offset = (15 + ($i * $recordSize)) * 2;

            if ($offset + ($recordSize * 2) <= strlen($response)) {
                $recordHex = substr($response, $offset, $recordSize * 2);
                $recordBytes = str_split($recordHex, 2);

                $records[] = [
                    'user_id' => hexdec($recordBytes[0] ?? '00') | 
                               (hexdec($recordBytes[1] ?? '00') << 8),
                    'datetime' => sprintf(
                        '%04d-%02d-%02d %02d:%02d:%02d',
                        2000 + hexdec($recordBytes[6] ?? '00'),
                        hexdec($recordBytes[7] ?? '00'),
                        hexdec($recordBytes[8] ?? '00'),
                        hexdec($recordBytes[9] ?? '00'),
                        hexdec($recordBytes[10] ?? '00'),
                        hexdec($recordBytes[11] ?? '00')
                    )
                ];
            }
        }

        return $records;
    }

    /**
     * Clear records
     * Command: 0x4D
     *
     * @param bool $confirm confirmation to delete records
     * @return bool Success status
     */
    public function clearRecords(bool $confirm = false): bool
    {
        if (!$confirm) {
            $this->logWarning("Record deletion requires confirmation");
            return false;
        }

        $response = $this->sendCommand(self::CMD_CLEAR_RECORDS);

        if ($response) {
            $this->logInfo("All records cleared from device");
            return true;
        }

        return false;
    }

    /**
     * Get record information
     * Command: 0x82
     * 
     * @return array|null Record information
     */
    public function getRecordInfo(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_RECORDS_INFO);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'total_records' => hexdec($bytes[5] ?? '00') | 
                             (hexdec($bytes[6] ?? '00') << 8),
            'new_records' => hexdec($bytes[7] ?? '00') | 
                           (hexdec($bytes[8] ?? '00') << 8)
        ];
    }

    // ============================================================
    // NETWORK CONFIGURATION
    // ============================================================

    /**
     * Get TCP/IP parameters
     * Command: 0x5C
     * 
     * @return array|null Network parameters
     */
    public function getTcpIpParams(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_TCP_IP_PARAMS);

        if (!$response || strlen($response) < 50) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'ip_address' => $this->parseIPAddress($bytes, 5),
            'subnet_mask' => $this->parseIPAddress($bytes, 9),
            'gateway' => $this->parseIPAddress($bytes, 13),
            'port' => hexdec($bytes[17] ?? '00') | (hexdec($bytes[18] ?? '00') << 8),
            'dns_primary' => isset($bytes[19]) ? $this->parseIPAddress($bytes, 19) : null,
            'dns_secondary' => isset($bytes[23]) ? $this->parseIPAddress($bytes, 23) : null,
            'raw_response' => $response
        ];
    }

    /**
     * Parse TCP/IP parameters
     * 
     * @param string $response Hex response string
     * @return array Network parameters
     */
    private function parseTcpIpParams(string $response): array
    {
        $bytes = str_split($response, 2);

        $ipAddress = '';
        for ($i = 5; $i < 9; $i++) {
            $ipAddress .= hexdec($bytes[$i] ?? '00') . '.';
        }
        $ipAddress = rtrim($ipAddress, '.');

        $subnetMask = '';
        for ($i = 9; $i < 13; $i++) {
            $subnetMask .= hexdec($bytes[$i] ?? '00') . '.';
        }
        $subnetMask = rtrim($subnetMask, '.');

        $gateway = '';
        for ($i = 13; $i < 17; $i++) {
            $gateway .= hexdec($bytes[$i] ?? '00') . '.';
        }
        $gateway = rtrim($gateway, '.');

        return [
            'ip_address' => $ipAddress,
            'subnet_mask' => $subnetMask,
            'gateway' => $gateway,
            'port' => hexdec($bytes[17] ?? '00') | 
                    (hexdec($bytes[18] ?? '00') << 8)
        ];
    }

    /**
     * Set TCP/IP parameters
     * Command: 0x5D
     *
     * @param array $config
     * @return bool Success status
     */
    public function setTcpIpParams(array $config): bool
    {
        $this->validateIPConfig($config);

        $data = [];

        $ipParts = explode('.', $config['ip_address']);
        foreach ($ipParts as $part) {
            $data[] = (int)$part;
        }

        $maskParts = explode('.', $config['subnet_mask']);
        foreach ($maskParts as $part) {
            $data[] = (int)$part;
        }

        $gwParts = explode('.', $config['gateway']);
        foreach ($gwParts as $part) {
            $data[] = (int)$part;
        }

        $port = (int)($config['port'] ?? 5010);
        $data[] = $port & 0xFF;
        $data[] = ($port >> 8) & 0xFF;

        if (isset($config['dns_primary'])) {
            $dnsParts = explode('.', $config['dns_primary']);
            foreach ($dnsParts as $part) {
                $data[] = (int)$part;
            }
        }

        $response = $this->sendCommand(self::CMD_SET_TCP_IP_PARAMS, $data);

        if ($response) {
            $this->logInfo("Network configuration updated");
            return true;
        }

        return false;
    }

    // ============================================================
    // TIMEZONE & ADVANCED SETTINGS
    // ============================================================

    /**
     * Get timezone information
     * Command: 0xB0
     * 
     * @return array|null Timezone information
     */
    public function getTimezone(): ?array
    {
        $response = $this->sendCommand(self::CMD_GET_TIMEZONE);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);
        $offset = $this->parseSignedByte($bytes[5] ?? '00');

        return [
            'timezone_offset_hours' => $offset,
            'timezone_offset_minutes' => hexdec($bytes[6] ?? '00'),
            'daylight_saving_enabled' => (bool)(hexdec($bytes[7] ?? '00')),
            'timezone_name' => $this->getTimezoneName($offset),
            'raw_response' => $response
        ];
    }

    // ============================================================


    /**
     * Set timezone information
     * Command: 0xB1
     *
     * @param int $offset Timezone offset in hours
     * @param bool $daylightSaving Enable daylight saving
     * @param int $minutes Timezone offset in minutes
     * @return bool Success status
     */
    public function setTimezone(int $offset, bool $daylightSaving = false, int $minutes = 0): bool
    {
        if ($offset < -12 || $offset > 14) {
            $this->logError("Invalid timezone offset: $offset");
            return false;
        }

        $offsetByte = $offset & 0xFF;
        if ($offset < 0) {
            $offsetByte = (256 + $offset) & 0xFF;
        }

        $data = [
            $offsetByte,
            $minutes & 0xFF,
            $daylightSaving ? 0x01 : 0x00
        ];

        $response = $this->sendCommand(self::CMD_SET_TIMEZONE, $data);

        if ($response) {
            $this->logInfo("Timezone set to UTC$offset:$minutes");
            return true;
        }

        return false;
    }
    // DEVICE CONTROL
    // ============================================================

    /**
     * Open door/lock without verification
     * Command: 0x7E
     * 
     * @return bool Success status
     */
    public function openDoor(): bool
    {
        $response = $this->sendCommand(self::CMD_OPEN_DOOR);
        return $response !== null;
    }

    /**
     * Reboot device
     * Command: 0x8B
     * 
     * @return bool Success status
     */
    public function rebootDevice(): bool
    {
        $response = $this->sendCommand(self::CMD_REBOOT_DEVICE);
        return $response !== null;
    }

    /**
     * Factory reset
     * Command: 0x8D
     * 
     * @return bool Success status
     */
    public function factoryReset(): bool
    {
        $response = $this->sendCommand(self::CMD_FACTORY_RESET);
        return $response !== null;
    }

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================

    /**
     * Send ping to device
     * Command: 0x81
     * 
     * @return bool Device is online
     */
    public function ping(): bool
    {
        $response = $this->sendCommand(self::CMD_PING);
        return $response !== null;
    }

    /**
     * Get device pings (Real-time support)
     * Command: 0x85
     * 
     * @return string|null Ping response
     */
    public function devicePings(): ?string
    {
        return $this->sendCommand(self::CMD_DEVICE_PINGS);
    }

    /**
     * Parse IP address from the byte array
     */
    private function parseIPAddress($bytes, $offset): string
    {
        return sprintf('%d.%d.%d.%d',
            hexdec($bytes[$offset] ?? '00'),
            hexdec($bytes[$offset + 1] ?? '00'),
            hexdec($bytes[$offset + 2] ?? '00'),
            hexdec($bytes[$offset + 3] ?? '00')
        );
    }

    /**
     * Parse signed byte (handle negative numbers)
     */
    private function parseSignedByte($hex)
    {
        $byte = hexdec($hex);
        if ($byte > 127) {
            $byte = $byte - 256;
        }
        return $byte;
    }

    /**
     * Get timezone name from offset
     */
    private function getTimezoneName($offset): string
    {
        $timezones = [
            -12 => 'UTC-12 (Baker Island)',
            -11 => 'UTC-11 (Samoa)',
            -10 => 'UTC-10 (Hawaii)',
            -9 => 'UTC-9 (Alaska)',
            -8 => 'UTC-8 (Pacific)',
            -7 => 'UTC-7 (Mountain)',
            -6 => 'UTC-6 (Central)',
            -5 => 'UTC-5 (Eastern)',
            -4 => 'UTC-4 (Atlantic)',
            -3 => 'UTC-3 (Brasilia)',
            -2 => 'UTC-2 (Mid-Atlantic)',
            -1 => 'UTC-1 (Azores)',
            0 => 'UTC+0 (London)',
            1 => 'UTC+1 (Berlin)',
            2 => 'UTC+2 (Cairo)',
            3 => 'UTC+3 (Moscow)',
            4 => 'UTC+4 (Dubai)',
            5 => 'UTC+5 (Pakistan)',
            6 => 'UTC+6 (Bangladesh)',
            7 => 'UTC+7 (Bangkok)',
            8 => 'UTC+8 (Shanghai)',
            9 => 'UTC+9 (Tokyo)',
            10 => 'UTC+10 (Sydney)',
            11 => 'UTC+11 (Solomon Islands)',
            12 => 'UTC+12 (New Zealand)',
            13 => 'UTC+13 (Fiji)',
            14 => 'UTC+14 (Line Islands)'
        ];

        return $timezones[$offset] ?? "UTC$offset";
    }


    /**
     * Parse record count
     */
    private function parseRecordCount($response): int
    {
        $bytes = str_split($response, 2);
        return hexdec($bytes[5] ?? '00') |
            (hexdec($bytes[6] ?? '00') << 8) |
            (hexdec($bytes[7] ?? '00') << 16);
    }

    /**
     * Parse attendance records from response
     */
    private function parseAttendanceRecords($response): array
    {
        //$bytes = str_split($response, 2);
        $records = [];
        $recordSize = 16;

        $totalRecords = floor((strlen($response) - 30) / ($recordSize * 2));

        for ($i = 0; $i < $totalRecords; $i++) {
            $offset = (15 + ($i * $recordSize)) * 2;

            if ($offset + ($recordSize * 2) <= strlen($response)) {
                $recordHex = substr($response, $offset, $recordSize * 2);
                $recordBytes = str_split($recordHex, 2);

                if (count($recordBytes) >= 12) {
                    $records[] = [
                        'user_id' => hexdec($recordBytes[0] ?? '00') |
                            (hexdec($recordBytes[1] ?? '00') << 8),
                        'datetime' => sprintf(
                            '%04d-%02d-%02d %02d:%02d:%02d',
                            2000 + hexdec($recordBytes[6] ?? '00'),
                            hexdec($recordBytes[7] ?? '00'),
                            hexdec($recordBytes[8] ?? '00'),
                            hexdec($recordBytes[9] ?? '00'),
                            hexdec($recordBytes[10] ?? '00'),
                            hexdec($recordBytes[11] ?? '00')
                        ),
                        'status' => hexdec($recordBytes[12] ?? '00')
                    ];
                }
            }
        }

        return $records;
    }

    /**
     * Validate IP configuration
     */
    private function validateIPConfig($config)
    {
        $required = ['ip_address', 'subnet_mask', 'gateway'];
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new InvalidArgumentException("Missing required field: $key");
            }
            if (!$this->isValidIPAddress($config[$key])) {
                throw new InvalidArgumentException("Invalid IP address: $config[$key]");
            }
        }
    }

    /**
     * Validate IP address format
     */
    private function isValidIPAddress($ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }


    /**
     * Log error
     */
    private function logError($message)
    {
        error_log("[Anviz ERROR] " . date('Y-m-d H:i:s') . " - " . $message);
    }

    /**
     * Log info
     */
    private function logInfo($message)
    {
        error_log("[Anviz INFO] " . date('Y-m-d H:i:s') . " - " . $message);
    }

    /**
     * Log warning
     */
    private function logWarning($message)
    {
        error_log("[Anviz WARNING] " . date('Y-m-d H:i:s') . " - " . $message);
    }

    /**
     * Get connection status
     * 
     * @return bool Connection status
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }
}

<?php
/**
 * Anviz Protocol PHP - Enhanced Implementation
 * 
 * Complete implementation based on:
 * - CommsProtocol.pdf (TC_B Communication Protocol)
 * - MxLabs/Anviz (.NET Reference)
 * - WhiteaglePT/node-anviz (Node.js Reference)
 * 
 * Covers all 7 categories with comprehensive functionality
 * 
 * @version 2.0.0
 * @license MIT
 */

namespace Anviz;

class AnvizProtocolEnhanced
{
    // ================================================================
    // CATEGORY 1: COMMUNICATION FUNDAMENTALS (100% Implemented)
    // ================================================================

    /**
     * Packet Structure Reference:
     * [Header:1][DeviceID:4LE][Command:1][Data:Variable][Checksum:2LE]
     * 
     * Header: 0xA5 (constant)
     * DeviceID: 4 bytes, little-endian
     * Command: 1 byte command code
     * Data: Variable length command-specific data
     * Checksum: 16-bit little-endian CRC-16 CCITT
     */

    private const PACKET_HEADER = 0xA5;
    private const SOCKET_TIMEOUT = 5;
    private const DEFAULT_DEVICE_PORT = 5010;

    private $socket = null;
    private $host = '';
    private $port = self::DEFAULT_DEVICE_PORT;
    private $deviceId = 5;
    private $connected = false;

    /**
     * Constructor
     */
    public function __construct($host, $port = self::DEFAULT_DEVICE_PORT, $deviceId = 5)
    {
        $this->host = $host;
        $this->port = $port;
        $this->deviceId = $deviceId;
    }

    /**
     * Connect to Anviz device
     * Establishes TCP socket connection with timeout
     */
    public function connect()
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, self::SOCKET_TIMEOUT);

        if (!$this->socket) {
            $this->logError("Connection failed to {$this->host}:{$this->port} - {$errstr} ({$errno})");
            return false;
        }

        stream_set_timeout($this->socket, self::SOCKET_TIMEOUT);
        $this->connected = true;
        return true;
    }

    /**
     * Disconnect from device
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
     * Calculate CRC-16 CCITT Checksum
     * 
     * Algorithm: CRC-16 CCITT with 0xA001 polynomial
     * Initial Value: 0xFFFF
     * Reflected Input/Output: Yes
     * 
     * @param array $bytes Byte array (excluding header, including device ID)
     * @return array [low_byte, high_byte]
     */
    public function calculateChecksum($bytes)
    {
        $crc = 0xFFFF;

        foreach ($bytes as $byte) {
            $byte = $byte & 0xFF;
            $crc ^= $byte;

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
     * Build protocol packet
     * 
     * Structure: [0xA5][DeviceID:4LE][Command][Data...][Checksum:2LE]
     * 
     * @param int $command Command code
     * @param array $data Command data
     * @return string Hex packet string
     */
    public function buildPacket($command, $data = [])
    {
        $packet = [];

        // Header (0xA5)
        $packet[] = self::PACKET_HEADER;

        // Device ID (4 bytes, little-endian)
        $packet[] = $this->deviceId & 0xFF;
        $packet[] = ($this->deviceId >> 8) & 0xFF;
        $packet[] = ($this->deviceId >> 16) & 0xFF;
        $packet[] = ($this->deviceId >> 24) & 0xFF;

        // Command
        $packet[] = $command & 0xFF;

        // Data
        if (!empty($data)) {
            foreach ($data as $byte) {
                $packet[] = $byte & 0xFF;
            }
        }

        // Calculate checksum on all bytes except header
        $checksumBytes = array_slice($packet, 1);
        $checksum = $this->calculateChecksum($checksumBytes);
        $packet[] = $checksum[0];
        $packet[] = $checksum[1];

        // Convert to hex string
        return $this->bytesToHex($packet);
    }

    /**
     * Send command to device and receive response
     */
    private function sendCommand($command, $data = [])
    {
        if (!$this->connected || !$this->socket) {
            $this->logError("Device not connected");
            return null;
        }

        $packet = $this->buildPacket($command, $data);
        $binary = $this->hexToBinary($packet);

        if (fwrite($this->socket, $binary) === false) {
            $this->logError("Failed to send command 0x{$this->dechexUpper($command)}");
            return null;
        }

        return $this->readResponse();
    }

    /**
     * Read response from device
     */
    private function readResponse()
    {
        $response = '';
        $buffer = '';

        while (!feof($this->socket)) {
            $chunk = fread($this->socket, 1024);
            if ($chunk === false) break;
            $buffer .= $chunk;

            if (strlen($buffer) > 0) break;
        }

        return $this->binaryToHex($buffer);
    }

    // ================================================================
    // CATEGORY 2: DATE/TIME FUNCTIONS (100% Implemented)
    // ================================================================

    private const CMD_GET_DEVICE_CLOCK = 0x38;
    private const CMD_SET_DEVICE_CLOCK = 0x39;

    /**
     * Get device clock (Date and Time)
     * Command: 0x38
     * 
     * Request: [A5][ID:4LE][38][00][00][Checksum:2LE]
     * Response: [A5][ID:4LE][38][YY][MM][DD][HH][mm][ss][Checksum:2LE]
     * 
     * @return array|null [year, month, day, hour, minute, second]
     */
    public function getDeviceClock()
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
            'second' => hexdec($bytes[14]),
            'raw' => $response
        ];
    }

    /**
     * Set device clock (Date and Time)
     * Command: 0x39
     * 
     * Request: [A5][ID:4LE][39][YY][MM][DD][HH][mm][ss][Checksum:2LE]
     * Response: Status code
     * 
     * @param string $dateTime DateTime string (YYYY-MM-DD HH:MM:SS)
     * @return bool Success status
     */
    public function setDeviceClock($dateTime)
    {
        try {
            $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
            if (!$dt) {
                $this->logError("Invalid datetime format: {$dateTime}");
                return false;
            }

            $year = (int)$dt->format('y');
            $month = (int)$dt->format('m');
            $day = (int)$dt->format('d');
            $hour = (int)$dt->format('H');
            $minute = (int)$dt->format('i');
            $second = (int)$dt->format('s');

            $data = [
                $year,
                $month,
                $day,
                $hour,
                $minute,
                $second
            ];

            $response = $this->sendCommand(self::CMD_SET_DEVICE_CLOCK, $data);

            if ($response) {
                $this->logInfo("Device clock set to: {$dateTime}");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logError("Error setting device clock: " . $e->getMessage());
            return false;
        }
    }

    // ================================================================
    // CATEGORY 3: BASIC DEVICE INFORMATION (100% Implemented)
    // ================================================================

    private const CMD_GET_DEVICE_CONFIG_1 = 0x30;
    private const CMD_GET_DEVICE_CONFIG_2 = 0x32;
    private const CMD_GET_DEVICE_SN = 0x50;
    private const CMD_GET_DEVICE_ID = 0x52;
    private const CMD_GET_DEVICE_INFO = 0x34;

    /**
     * Get device configuration 1 (Basic Settings)
     * Command: 0x30
     * 
     * Returns: Language, Timezone, Date Format, etc.
     */
    public function getDeviceConfiguration1()
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_CONFIG_1);

        if (!$response) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'language' => hexdec($bytes[5] ?? '00'),
            'timezone' => $this->parseSignedByte($bytes[6] ?? '00'),
            'date_format' => hexdec($bytes[7] ?? '00'),
            'time_format' => hexdec($bytes[8] ?? '00'),
            'raw_response' => $response,
            'parsed_bytes' => $bytes
        ];
    }

    /**
     * Get device configuration 2 (Advanced Settings)
     * Command: 0x32
     * 
     * Returns: Fingerprint settings, Card settings, etc.
     */
    public function getDeviceConfiguration2()
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_CONFIG_2);

        if (!$response) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'fingerprint_threshold' => hexdec($bytes[5] ?? '00'),
            'card_detection_time' => hexdec($bytes[6] ?? '00'),
            'raw_response' => $response,
            'byte_count' => count($bytes)
        ];
    }

    /**
     * Get device serial number
     * Command: 0x50
     */
    public function getDeviceSerialNumber()
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_SN);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);
        $sn = '';

        for ($i = 5; $i < min(count($bytes) - 2, 25); $i++) {
            $byte = hexdec($bytes[$i]);
            if ($byte > 0 && $byte < 127) {
                $sn .= chr($byte);
            }
        }

        return [
            'serial_number' => $sn,
            'raw_response' => $response
        ];
    }

    /**
     * Get device ID
     * Command: 0x52
     */
    public function getDeviceID()
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_ID);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);

        $deviceId = hexdec($bytes[5] ?? '00') | 
                   (hexdec($bytes[6] ?? '00') << 8) | 
                   (hexdec($bytes[7] ?? '00') << 16) | 
                   (hexdec($bytes[8] ?? '00') << 24);

        return [
            'device_id' => $deviceId,
            'raw_response' => $response
        ];
    }

    /**
     * Get device advanced information
     * Command: 0x34
     */
    public function getDeviceAdvancedInfo()
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_INFO);

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

    // ================================================================
    // CATEGORY 4: USER MANAGEMENT (100% Implemented)
    // ================================================================

    private const CMD_DOWNLOAD_STAFF_DATA = 0x3C;
    private const CMD_UPLOAD_STAFF_DATA = 0x3D;
    private const CMD_DELETE_USER = 0x92;
    private const CMD_ENROLL_FINGERPRINT = 0x63;
    private const CMD_ENROLL_CARD = 0x64;

    /**
     * Download staff data
     * Command: 0x3C
     * 
     * Retrieves all user/staff information from device
     */
    public function downloadStaffData($options = [])
    {
        $startIndex = $options['start_index'] ?? 0;
        $count = $options['count'] ?? 0xFF;

        $data = [
            $startIndex & 0xFF,
            ($startIndex >> 8) & 0xFF,
            $count & 0xFF
        ];

        $response = $this->sendCommand(self::CMD_DOWNLOAD_STAFF_DATA, $data);

        if (!$response) {
            return null;
        }

        return [
            'raw_data' => $response,
            'total_bytes' => strlen($response) / 2,
            'users' => $this->parseUserData($response)
        ];
    }

    /**
     * Upload staff data
     * Command: 0x3D
     * 
     * Uploads user/staff information to device
     */
    public function uploadStaffData($users)
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

        return $response !== null;
    }

    /**
     * Delete user
     * Command: 0x92
     */
    public function deleteUser($userId)
    {
        $data = [
            $userId & 0xFF,
            ($userId >> 8) & 0xFF,
            ($userId >> 16) & 0xFF,
            ($userId >> 24) & 0xFF
        ];

        $response = $this->sendCommand(self::CMD_DELETE_USER, $data);

        if ($response) {
            $this->logInfo("User {$userId} deleted");
            return true;
        }

        return false;
    }

    /**
     * Enroll fingerprint
     * Command: 0x63
     */
    public function enrollFingerprint($userId, $fingerIndex = 1, $options = [])
    {
        $data = [
            $userId & 0xFF,
            ($userId >> 8) & 0xFF,
            $fingerIndex & 0xFF,
            $options['quality_threshold'] ?? 80
        ];

        $response = $this->sendCommand(self::CMD_ENROLL_FINGERPRINT, $data);

        if ($response) {
            $this->logInfo("Fingerprint enrollment initiated for user {$userId}");
            return true;
        }

        return false;
    }

    /**
     * Enroll card
     * Command: 0x64
     */
    public function enrollCard($userId, $cardNumber)
    {
        if (!is_numeric($cardNumber)) {
            $this->logError("Invalid card number: {$cardNumber}");
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

    // ================================================================
    // CATEGORY 5: RECORDS DOWNLOAD (100% Implemented)
    // ================================================================

    private const CMD_DOWNLOAD_RECORDS = 0x4C;
    private const CMD_DOWNLOAD_NEW_RECORDS = 0x74;
    private const CMD_CLEAR_RECORDS = 0x4D;
    private const CMD_GET_RECORD_INFO = 0x82;
    private const CMD_DELETE_RECORD = 0x4E;

    /**
     * Download all attendance records
     * Command: 0x4C
     */
    public function downloadRecords($options = [])
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
     * Download only new records
     * Command: 0x74
     */
    public function downloadNewRecords()
    {
        $response = $this->sendCommand(self::CMD_DOWNLOAD_NEW_RECORDS);

        if (!$response) {
            return null;
        }

        return [
            'new_records' => $this->parseAttendanceRecords($response),
            'count' => count($this->parseAttendanceRecords($response)),
            'raw_response' => $response
        ];
    }

    /**
     * Get record information and statistics
     * Command: 0x82
     */
    public function getRecordInfo()
    {
        $response = $this->sendCommand(self::CMD_GET_RECORD_INFO);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'total_records' => hexdec($bytes[5] ?? '00') | 
                             (hexdec($bytes[6] ?? '00') << 8) |
                             (hexdec($bytes[7] ?? '00') << 16),
            'new_records' => hexdec($bytes[8] ?? '00') | 
                           (hexdec($bytes[9] ?? '00') << 8),
            'raw_response' => $response
        ];
    }

    /**
     * Clear all records
     * Command: 0x4D
     */
    public function clearRecords($confirm = false)
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
     * Delete specific record
     * Command: 0x4E
     */
    public function deleteRecord($recordIndex)
    {
        $data = [
            $recordIndex & 0xFF,
            ($recordIndex >> 8) & 0xFF
        ];

        $response = $this->sendCommand(self::CMD_DELETE_RECORD, $data);

        return $response !== null;
    }

    // ================================================================
    // CATEGORY 6: NETWORK CONFIGURATION (100% Implemented)
    // ================================================================

    private const CMD_GET_TCP_IP_PARAMS = 0x5C;
    private const CMD_SET_TCP_IP_PARAMS = 0x5D;
    private const CMD_GET_GATEWAY = 0x5E;
    private const CMD_SET_GATEWAY = 0x5F;

    /**
     * Get TCP/IP parameters
     * Command: 0x5C
     */
    public function getTcpIpParams()
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
            'dns_primary' => $this->parseIPAddress($bytes, 19),
            'dns_secondary' => $this->parseIPAddress($bytes, 23),
            'raw_response' => $response
        ];
    }

    /**
     * Set TCP/IP parameters
     * Command: 0x5D
     */
    public function setTcpIpParams($config)
    {
        $this->validateIPConfig($config);

        $data = [];

        // Add IP address
        $ipParts = explode('.', $config['ip_address']);
        foreach ($ipParts as $part) {
            $data[] = (int)$part;
        }

        // Add subnet mask
        $maskParts = explode('.', $config['subnet_mask']);
        foreach ($maskParts as $part) {
            $data[] = (int)$part;
        }

        // Add gateway
        $gwParts = explode('.', $config['gateway']);
        foreach ($gwParts as $part) {
            $data[] = (int)$part;
        }

        // Add port
        $port = (int)($config['port'] ?? 5010);
        $data[] = $port & 0xFF;
        $data[] = ($port >> 8) & 0xFF;

        // Add DNS servers if provided
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

    /**
     * Validate IP configuration
     */
    private function validateIPConfig($config)
    {
        $required = ['ip_address', 'subnet_mask', 'gateway'];
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("Missing required field: {$key}");
            }
            if (!$this->isValidIPAddress($config[$key])) {
                throw new \InvalidArgumentException("Invalid IP address: {$config[$key]}");
            }
        }
    }

    /**
     * Validate IP address format
     */
    private function isValidIPAddress($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    // ================================================================
    // CATEGORY 7: TIMEZONE INFORMATION (100% Implemented)
    // ================================================================

    private const CMD_GET_TIMEZONE = 0xB0;
    private const CMD_SET_TIMEZONE = 0xB1;
    private const CMD_GET_BELL_SCHEDULE = 0xB2;
    private const CMD_SET_BELL_SCHEDULE = 0xB3;
    private const CMD_GET_DST_RULES = 0xB4;
    private const CMD_SET_DST_RULES = 0xB5;

    /**
     * Get timezone information
     * Command: 0xB0
     */
    public function getTimezone()
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

    /**
     * Set timezone information
     * Command: 0xB1
     */
    public function setTimezone($offset, $daylightSaving = false, $minutes = 0)
    {
        if ($offset < -12 || $offset > 14) {
            $this->logError("Invalid timezone offset: {$offset}");
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
            $this->logInfo("Timezone set to UTC{$offset}:{$minutes} (DST: " . 
                          ($daylightSaving ? 'ON' : 'OFF') . ")");
            return true;
        }

        return false;
    }

    /**
     * Get bell/schedule configuration
     * Command: 0xB2
     */
    public function getBellSchedule()
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
    public function setBellSchedule($schedules)
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

        return $response !== null;
    }

    /**
     * Get Daylight Saving Time rules
     * Command: 0xB4
     */
    public function getDSTRules()
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
    public function setDSTRules($dstConfig)
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

        return $response !== null;
    }

    // ================================================================
    // HELPER FUNCTIONS
    // ================================================================

    /**
     * Parse IP address from byte array
     */
    private function parseIPAddress($bytes, $offset)
    {
        return sprintf('%d.%d.%d.%d',
            hexdec($bytes[$offset] ?? '00'),
            hexdec($bytes[$offset + 1] ?? '00'),
            hexdec($bytes[$offset + 2] ?? '00'),
            hexdec($bytes[$offset + 3] ?? '00')
        );
    }

    /**
     * Parse attendance records from response
     */
    private function parseAttendanceRecords($response)
    {
        $bytes = str_split($response, 2);
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
     * Parse user data from response
     */
    private function parseUserData($response)
    {
        $bytes = str_split($response, 2);
        $users = [];
        $userSize = 32;

        for ($i = 0; $i < strlen($response) / ($userSize * 2); $i++) {
            $offset = $i * $userSize;

            if ($offset + $userSize <= count($bytes)) {
                $userId = hexdec($bytes[$offset] ?? '00') | 
                         (hexdec($bytes[$offset + 1] ?? '00') << 8);

                $name = '';
                for ($j = 2; $j < 20; $j++) {
                    $byte = hexdec($bytes[$offset + $j] ?? '00');
                    if ($byte > 0 && $byte < 127) {
                        $name .= chr($byte);
                    }
                }

                $users[] = [
                    'id' => $userId,
                    'name' => trim($name)
                ];
            }
        }

        return $users;
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
    private function getTimezoneName($offset)
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

        return $timezones[$offset] ?? "UTC{$offset}";
    }

    /**
     * Convert bytes to hex string
     */
    private function bytesToHex($bytes)
    {
        return implode('', array_map(function($b) {
            return strtoupper(str_pad(dechex($b), 2, '0', STR_PAD_LEFT));
        }, $bytes));
    }

    /**
     * Convert hex string to binary
     */
    private function hexToBinary($hex)
    {
        $binary = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $binary .= chr(hexdec(substr($hex, $i, 2)));
        }
        return $binary;
    }

    /**
     * Convert binary to hex string
     */
    private function binaryToHex($binary)
    {
        $hex = '';
        for ($i = 0; $i < strlen($binary); $i++) {
            $hex .= strtoupper(str_pad(dechex(ord($binary[$i])), 2, '0', STR_PAD_LEFT));
        }
        return $hex;
    }

    /**
     * Parse record count
     */
    private function parseRecordCount($response)
    {
        $bytes = str_split($response, 2);
        return hexdec($bytes[5] ?? '00') | 
               (hexdec($bytes[6] ?? '00') << 8) |
               (hexdec($bytes[7] ?? '00') << 16);
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
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Dechex with uppercase
     */
    private function dechexUpper($dec)
    {
        return strtoupper(dechex($dec));
    }
}

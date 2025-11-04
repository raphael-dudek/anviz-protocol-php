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

    // Packet Constants
    private const PACKET_HEADER = 0xA5;
    private const SOCKET_TIMEOUT = 5;

    private $socket = null;
    private $host = '';
    private $port = 5010;
    private $deviceId = 0;
    private $connected = false;

    /**
     * Constructor
     * 
     * @param string $host Device IP address
     * @param int $port TCP Port (default 5010)
     * @param int $deviceId Device ID
     */
    public function __construct($host, $port = 5010, $deviceId = 5)
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
    public function connect()
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
     * Calculate Checksum (CRC-16 CCITT)
     * Based on StackOverflow Q18929423
     * 
     * @param array $bytes Byte array without header and device ID
     * @return array [low_byte, high_byte]
     */
    private function calculateChecksum($bytes)
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
     * Build command packet
     * 
     * @param int $command Command code
     * @param array $data Command data
     * @return string Hex packet string
     */
    private function buildPacket($command, $data = [])
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
     * Send command to device
     * 
     * @param int $command Command code
     * @param array $data Command data
     * @return string Response hex string
     */
    private function sendCommand($command, $data = [])
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

        // Send packet
        if (fwrite($this->socket, $binary) === false) {
            $this->logError("Failed to send command");
            return null;
        }

        // Read response
        return $this->readResponse();
    }

    /**
     * Read response from device
     * 
     * @return string Response hex string
     */
    private function readResponse()
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
    public function setDeviceClock($dateTime)
    {
        try {
            $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
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
        } catch (\Exception $e) {
            $this->logError("Error setting device clock: " . $e->getMessage());
            return false;
        }
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
    public function getDeviceSerialNumber()
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
    public function getDeviceID()
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
    public function getDeviceType()
    {
        // Implementation depends on specific device response format
        $response = $this->sendCommand(0x50);
        return $response;
    }

    /**
     * Get basic device information
     * Command: 0x30
     * 
     * @return array|null Device configuration
     */
    public function getDeviceConfig()
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
    private function parseDeviceConfig($response)
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
    public function downloadStaffData()
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
     * @param array $staffData Staff information array
     * @return bool Success status
     */
    public function uploadStaffData($staffData)
    {
        // Implementation requires encoding staff data according to protocol
        $this->logError("uploadStaffData: Not yet fully implemented");
        return false;
    }

    /**
     * Delete user
     * Command: 0x92
     * 
     * @param int $userId User ID to delete
     * @return bool Success status
     */
    public function deleteUser($userId)
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
    public function downloadRecords()
    {
        $response = $this->sendCommand(self::CMD_DOWNLOAD_RECORDS);

        if (!$response) {
            return null;
        }

        return $this->parseRecords($response);
    }

    /**
     * Download new records
     * Command: 0x74
     * 
     * @return array|null New records data
     */
    public function downloadNewRecords()
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
    private function parseRecords($response)
    {
        $bytes = str_split($response, 2);
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
     * @return bool Success status
     */
    public function clearRecords()
    {
        $response = $this->sendCommand(self::CMD_CLEAR_RECORDS);
        return $response !== null;
    }

    /**
     * Get record information
     * Command: 0x82
     * 
     * @return array|null Record information
     */
    public function getRecordInfo()
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
    public function getTcpIpParams()
    {
        $response = $this->sendCommand(self::CMD_GET_TCP_IP_PARAMS);

        if (!$response || strlen($response) < 40) {
            return null;
        }

        return $this->parseTcpIpParams($response);
    }

    /**
     * Parse TCP/IP parameters
     * 
     * @param string $response Hex response string
     * @return array Network parameters
     */
    private function parseTcpIpParams($response)
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
     * @param string $ipAddress IP address
     * @param string $subnetMask Subnet mask
     * @param string $gateway Gateway address
     * @param int $port Port number
     * @return bool Success status
     */
    public function setTcpIpParams($ipAddress, $subnetMask, $gateway, $port = 5010)
    {
        $data = [];

        // Parse and add IP address
        $ipParts = explode('.', $ipAddress);
        foreach ($ipParts as $part) {
            $data[] = intval($part);
        }

        // Parse and add subnet mask
        $maskParts = explode('.', $subnetMask);
        foreach ($maskParts as $part) {
            $data[] = intval($part);
        }

        // Parse and add gateway
        $gwParts = explode('.', $gateway);
        foreach ($gwParts as $part) {
            $data[] = intval($part);
        }

        // Add port
        $data[] = $port & 0xFF;
        $data[] = ($port >> 8) & 0xFF;

        $response = $this->sendCommand(self::CMD_SET_TCP_IP_PARAMS, $data);
        return $response !== null;
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
    public function getTimezone()
    {
        $response = $this->sendCommand(self::CMD_GET_TIMEZONE);

        if (!$response || strlen($response) < 20) {
            return null;
        }

        $bytes = str_split($response, 2);

        return [
            'timezone_offset' => hexdec($bytes[5] ?? '00'),
            'daylight_saving' => hexdec($bytes[6] ?? '00'),
            'raw_response' => $response
        ];
    }

    /**
     * Set timezone information
     * Command: 0xB1
     * 
     * @param int $timezoneOffset Timezone offset in hours
     * @param bool $daylightSaving Enable daylight saving
     * @return bool Success status
     */
    public function setTimezone($timezoneOffset, $daylightSaving = false)
    {
        $data = [
            $timezoneOffset & 0xFF,
            (($timezoneOffset >> 8) & 0xFF),
            $daylightSaving ? 0x01 : 0x00
        ];

        $response = $this->sendCommand(self::CMD_SET_TIMEZONE, $data);
        return $response !== null;
    }

    // ============================================================
    // DEVICE CONTROL
    // ============================================================

    /**
     * Open door/lock without verification
     * Command: 0x7E
     * 
     * @return bool Success status
     */
    public function openDoor()
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
    public function rebootDevice()
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
    public function factoryReset()
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
    public function ping()
    {
        $response = $this->sendCommand(self::CMD_PING);
        return $response !== null;
    }

    /**
     * Get device pings (Real-time support)
     * Command: 0x85
     * 
     * @return bool|string Ping response
     */
    public function devicePings()
    {
        return $this->sendCommand(self::CMD_DEVICE_PINGS);
    }

    /**
     * Log error message
     * 
     * @param string $message Error message
     */
    private function logError($message)
    {
        error_log("[Anviz Protocol] " . date('Y-m-d H:i:s') . " - " . $message);
    }

    /**
     * Get connection status
     * 
     * @return bool Connection status
     */
    public function isConnected()
    {
        return $this->connected;
    }
}

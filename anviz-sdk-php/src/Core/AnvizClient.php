<?php

namespace Anviz\SDK\Core;

use Anviz\SDK\Communication\TcpClient;
use Anviz\SDK\Models\DeviceInfo;
use Anviz\SDK\Models\UserInfo;
use Anviz\SDK\Models\AttendanceRecord;
use Anviz\SDK\Models\NetworkConfig;
use Anviz\SDK\Models\TimezoneConfig;
use Anviz\SDK\Models\BellSchedule;
use Anviz\SDK\Utils\BinaryHelper;
use Anviz\SDK\Utils\Logger;
use Exception;

/**
 * Hauptklasse für die Kommunikation mit Anviz-Geräten
 */
class AnvizClient
{
    // Befehlscodes gemäß PROTOCOL.md und C# SDK
    const CMD_GET_INFO_1 = 0x30;
    const CMD_SET_INFO_1 = 0x31;
    const CMD_GET_INFO_2 = 0x32;
    const CMD_SET_INFO_2 = 0x33;
    const CMD_GET_ADVANCED_INFO = 0x34;
    const CMD_SET_ADVANCED_INFO = 0x35;
    const CMD_GET_DATETIME = 0x38;
    const CMD_SET_DATETIME = 0x39;
    const CMD_GET_NETWORK = 0x3A;
    const CMD_SET_NETWORK = 0x3B;
    const CMD_GET_RECORD_INFO = 0x3C;
    const CMD_DOWNLOAD_RECORDS = 0x40;
    const CMD_UPLOAD_RECORDS = 0x41;
    const CMD_DOWNLOAD_USER = 0x42;
    const CMD_UPLOAD_USER = 0x43;
    const CMD_DOWNLOAD_FINGERPRINT = 0x44;
    const CMD_UPLOAD_FINGERPRINT = 0x45;
    const CMD_GET_DEVICE_ID = 0x46;
    const CMD_SET_DEVICE_ID = 0x47;
    const CMD_GET_MODEL = 0x48;
    const CMD_DELETE_USER = 0x4C;
    const CMD_DELETE_USER_ALL = 0x4D;
    const CMD_CLEAR_RECORDS = 0x4E;
    
    // Zutritts-Zeitpläne und Klingelpläne (C# SDK konform)
    const CMD_GET_TIMEZONE = 0x50;
    const CMD_SET_TIMEZONE = 0x51;
    const CMD_GET_BELL_SCHEDULE = 0x54;
    const CMD_SET_BELL_SCHEDULE = 0x55;
    
    const CMD_GET_DEVICE_SN = 0x24;
    const CMD_SET_DEVICE_SN = 0x25;

    const CMD_ENROLL_FINGERPRINT = 0x5C;
    const CMD_UNLOCK_DOOR = 0x5E;
    const CMD_ENROLL_CARD = 0x64;

    const CMD_GET_ATTENDANCE_STATE_TABLE = 0x70;
    const CMD_SET_ATTENDANCE_STATE_TABLE = 0x71;
    const CMD_DOWNLOAD_STAFF_INFO = 0x72;
    const CMD_UPLOAD_STAFF_INFO = 0x73;

    const CMD_DOWNLOAD_NEW_RECORDS = 0x74;
    const CMD_INQUIRE_CARD = 0x7E;
    const CMD_PING = 0x7F;

    const CMD_REBOOT_DEVICE = 0x8B;
    const CMD_FACTORY_RESET = 0x8D;

    const CMD_SET_CONNECTION_PASSWORD = 0x04;

    // Veraltete Codes oder Spezial-Mapping
    const CMD_GET_TIMEZONE_OLD = 0xB0;
    const CMD_SET_TIMEZONE_OLD = 0xB1;
    const CMD_GET_BELL_SCHEDULE_OLD = 0xB2;
    const CMD_SET_BELL_SCHEDULE_OLD = 0xB3;

    private $host;
    private $port;
    private $deviceId;
    private $timeout;
    private $tcpClient;
    private $password = null;

    public function __construct(string $host, int $port = 5010, int $deviceId = 1, int $timeout = 10, string $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->deviceId = $deviceId;
        $this->timeout = $timeout;
        $this->password = $password;
        $this->tcpClient = new TcpClient($host, $port, $timeout);
        Logger::setLogLevel('DEBUG');
    }

    public function connect(): bool
    {
        Logger::info("Verbinde zu {$this->host}:{$this->port}");
        try {
            if (!$this->tcpClient->connect()) {
                return false;
            }

            if ($this->password !== null && $this->password !== "0" && $this->password !== "") {
                Logger::info("Verwende Kommunikationspasswort für Handshake");
                if (!$this->setConnectionPassword("admin", (string)$this->password)) {
                    Logger::error("Passwort-Authentifizierung fehlgeschlagen");
                    $this->disconnect();
                    return false;
                }
                Logger::info("Passwort-Authentifizierung erfolgreich");
            }
        } catch (Exception $e) {
            Logger::error("Verbindungsfehler: " . $e->getMessage());
            return false;
        }

        return true;
    }

    public function disconnect(): void
    {
        Logger::info("Trenne Verbindung");
        $this->tcpClient->disconnect();
    }

    public function isConnected(): bool
    {
        return $this->tcpClient->isConnected();
    }

    private function sendCommand(int $command, string $data = '', int $retries = 2): Response
    {
        Logger::debug("Sende Befehl 0x" . dechex($command));

        $request = new Request($this->deviceId, $command, $data);
        $requestData = $request->getPayloadString();
        Logger::debug("Gesendetes Paket: " . bin2hex($requestData));

        $attempt = 0;
        $responseData = '';
        
        while ($attempt <= $retries) {
            try {
                $this->tcpClient->send($requestData);
                
                // Wir lesen so lange, bis wir die erwartete Antwort erhalten
                // oder einen Timeout/Fehler haben.
                while (true) {
                    $responseData = $this->tcpClient->receive();
                    if (empty($responseData)) {
                        Logger::debug("Keine Daten vom TCP Client empfangen.");
                        break 2; // Raus aus beiden Schleifen, Retry
                    }
                    
                    $response = new Response($responseData);
                    
                    // ID Check
                    if ($response->getDeviceId() !== $this->deviceId && $this->deviceId !== 0) {
                        Logger::warn("Antwort von unerwarteter Geräte-ID erhalten: " . $response->getDeviceId() . " (Erwartet: " . $this->deviceId . ")");
                        // Wir akzeptieren die Antwort trotzdem, wenn es die richtige Antwort auf unser Kommando ist?
                        // Das C# SDK scheint die ID nicht strikt zu prüfen beim Match des ResponseCode.
                    }

                    // Spezialfall: Gerät sendet Ping (0x7F)
                    if ($response->getCommand() === self::CMD_PING && $command !== self::CMD_PING) {
                        Logger::debug("Eingehender Ping vom Gerät erhalten, sende Pong");
                        // Pong senden (Paket mit CMD_PING)
                        $pong = new Request($this->deviceId, self::CMD_PING);
                        $this->tcpClient->send($pong->getPayloadString());
                        continue; // Weiter auf die eigentliche Antwort warten
                    }
                    
                    // Prüfen ob es die Antwort auf unser Kommando ist
                    $expectedACK = ($command + 0x80) & 0xFF;
                    if ($response->getCommand() === $expectedACK || $response->getCommand() === $command) {
                        break 2; // Erfolgreich empfangen
                    }
                    
                    Logger::debug("Unerwartete Antwort erhalten (0x" . dechex($response->getCommand()) . "), ignoriere...");
                }
            } catch (Exception $e) {
                Logger::warn("Versuch " . ($attempt + 1) . " fehlgeschlagen: " . $e->getMessage());
                if ($attempt === $retries) throw $e;
                
                // Verbindung neu aufbauen bei Fehlern
                try {
                    $this->tcpClient->disconnect();
                    $this->tcpClient->connect();
                    // Erneut Authentifizieren falls nötig
                    if ($this->password !== null && $this->password !== "0" && $this->password !== "" && $command !== self::CMD_SET_CONNECTION_PASSWORD) {
                        $this->setConnectionPassword("admin", (string)$this->password);
                    }
                } catch (Exception $ce) {
                    Logger::error("Rekonnektierung fehlgeschlagen: " . $ce->getMessage());
                }
            }
            $attempt++;
            if ($attempt <= $retries) {
                Logger::debug("Keine Antwort, versuche erneut...");
                sleep(1); // 1s warten
            }
        }

        Logger::debug("Empfangene Daten: " . ($responseData ? bin2hex($responseData) : "Leer"));

        if (empty($responseData)) {
            throw new Exception("Keine Antwort vom Gerät erhalten");
        }

        $response = new Response($responseData);

        if (!$response->isValid()) {
            throw new Exception("Ungültige CRC in Antwort");
        }

        if ($response->getRet() !== 0) {
            // Spezialfall für Authentifizierung (0x04)
            if ($command === self::CMD_SET_CONNECTION_PASSWORD && $response->getRet() === 0) {
                 // Alles okay, getRet() prüft hier das RET Byte an der richtigen Stelle
            } else {
                throw new Exception("Gerät gibt Fehler zurück: " . $response->getRet());
            }
        }

        Logger::debug("Befehl erfolgreich abgeschlossen");
        return $response;
    }

    private function tryWithReconnect(callable $action, string $label): void
    {
        try {
            $action();
            return;
        } catch (Exception $e) {
            Logger::warn($label . " fehlgeschlagen: " . $e->getMessage());
        }

        try {
            $this->disconnect();
            $this->connect();
            $action();
        } catch (Exception $e) {
            Logger::warn($label . " fehlgeschlagen (nach Reconnect): " . $e->getMessage());
        }
    }

    public function ping(): bool
    {
        try {
            // Bei Ping senden wir CMD_PING (0x7F) und erwarten ACK_PING (0xFF)
            $response = $this->sendCommand(self::CMD_PING);
            // ACK_PING ist 0x7F + 0x80 = 0xFF
            return $response->getCommand() === 0xFF || $response->isSuccess();
        } catch (Exception $e) {
            Logger::warn("Ping fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Holt Info 1 (Befehl 0x30)
     */
    public function getInfo1(): array
    {
        $response = $this->sendCommand(self::CMD_GET_INFO_1);
        $data = $response->getData();
        
        if (count($data) >= 18) {
            return [
                'firmware_version' => BinaryHelper::readFixedString($data, 0, 8),
                'pass' => sprintf('%06x', ($data[8] << 16) | ($data[9] << 8) | $data[10]),
                'sleep_time' => $data[11],
                'volume' => $data[12],
                'language' => $data[13],
                'datetime_format' => $data[14],
                'attendance_state' => $data[15],
                'language_setting_flag' => $data[16],
                'command_version' => $data[17]
            ];
        }
        return [];
    }

    /**
     * Holt Info 2 (Befehl 0x32)
     */
    public function getInfo2(): array
    {
        $response = $this->sendCommand(self::CMD_GET_INFO_2);
        $data = $response->getData();
        
        if (count($data) >= 14) {
            return [
                'comparison_precision' => $data[0],
                'fixed_wiegand_head_code' => $data[1],
                'wiegand_option' => $data[2],
                'work_code_permission' => $data[3],
                'real_time_mode' => $data[4],
                'fp_auto_update' => $data[5],
                'relay_mode' => $data[6],
                'lock_delay' => $data[7],
                'memory_full_alarm' => ($data[8] << 8) | $data[9],
                'repeat_attendance_delay' => $data[11],
                'door_sensor_delay' => $data[12],
                'scheduled_bell_delay' => $data[13]
            ];
        }
        return [];
    }

    /**
     * Setzt Info 1 (Befehl 0x31)
     */
    public function setInfo1(int $pass, int $sleepTime, int $volume, int $language, int $dtFormat, int $attendanceState, int $langSettingFlag): bool
    {
        $data = chr(($pass >> 16) & 0xFF) . chr(($pass >> 8) & 0xFF) . chr($pass & 0xFF) .
                chr($sleepTime) . chr($volume) . chr($language) . chr($dtFormat) .
                chr($attendanceState) . chr($langSettingFlag) . chr(0); // Reserved
        
        $response = $this->sendCommand(self::CMD_SET_INFO_1, $data);
        return $response->isSuccess();
    }

    /**
     * Setzt Info 2 (Befehl 0x33)
     */
    public function setInfo2(array $config): bool
    {
        $data = chr($config['comparison_precision'] ?? 0);
        $data .= chr($config['fixed_wiegand_head_code'] ?? 0);
        $data .= chr($config['wiegand_option'] ?? 0);
        $data .= chr(($config['work_code_permission'] ?? false) ? 1 : 0);
        $data .= chr(($config['real_time_mode'] ?? false) ? 1 : 0);
        $data .= chr(($config['fp_auto_update'] ?? false) ? 1 : 0);
        $data .= chr($config['relay_mode'] ?? 0);
        $data .= chr($config['lock_delay'] ?? 0);
        
        $alarm = $config['memory_full_alarm'] ?? 0;
        $data .= chr(($alarm >> 16) & 0xFF) . chr(($alarm >> 8) & 0xFF) . chr($alarm & 0xFF);
        
        $data .= chr(0); // Reserved
        $data .= chr($config['repeat_attendance_delay'] ?? 0);
        $data .= chr($config['door_sensor_delay'] ?? 0);
        $data .= chr($config['scheduled_bell_delay'] ?? 0);
        $data .= chr(0); // Reserved
        
        $response = $this->sendCommand(self::CMD_SET_INFO_2, $data);
        return $response->isSuccess();
    }

    /**
     * Holt T&A Status-Tabelle (Befehl 0x70)
     */
    public function getAttendanceStateTable(): array
    {
        $response = $this->sendCommand(self::CMD_GET_ATTENDANCE_STATE_TABLE);
        $data = $response->getData();
        
        if (empty($data)) return [];
        
        $numStates = $data[0];
        $states = [];
        for ($i = 0; $i < $numStates; $i++) {
            $offset = 1 + ($i * 40); // Unicode BE names are 40 bytes (20 chars)
            $raw = implode(array_map('chr', array_slice($data, $offset, 40)));
            $raw = $this->trimUtf16BeNull($raw);
            $states[] = trim($this->decodeUtf16Be($raw), "\0");
        }
        return $states;
    }

    /**
     * Setzt T&A Status-Tabelle (Befehl 0x71)
     */
    public function setAttendanceStateTable(array $states): bool
    {
        $count = min(count($states), 16);
        $data = chr($count);
        
        for ($i = 0; $i < 16; $i++) {
            $state = $states[$i] ?? '';
            // Unicode BE (40 bytes for 20 chars)
            $encoded = $this->encodeUtf16Be($state);
            $data .= str_pad(substr($encoded, 0, 40), 40, "\0");
        }
        
        $response = $this->sendCommand(self::CMD_SET_ATTENDANCE_STATE_TABLE, $data);
        return $response->isSuccess();
    }

    private function decodeUtf16Be(string $raw): string
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
        }
        if (function_exists('iconv')) {
            $converted = iconv('UTF-16BE', 'UTF-8//IGNORE', $raw);
            if ($converted !== false) {
                return $converted;
            }
        }
        $bytes = array_values(unpack('C*', $raw));
        $latin1 = '';
        for ($i = 0; $i + 1 < count($bytes); $i += 2) {
            $hi = $bytes[$i];
            $lo = $bytes[$i + 1];
            if ($hi === 0 && $lo === 0) {
                continue;
            }
            if ($hi === 0) {
                $latin1 .= chr($lo);
            } else {
                $latin1 .= '?';
            }
        }
        return utf8_encode($latin1);
    }

    private function trimUtf16BeNull(string $raw): string
    {
        $len = strlen($raw);
        for ($i = 0; $i + 1 < $len; $i += 2) {
            if ($raw[$i] === "\0" && $raw[$i + 1] === "\0") {
                return substr($raw, 0, $i);
            }
        }
        return $raw;
    }

    private function truncateUtf16Be(string $data, int $maxBytes): string
    {
        $maxBytes = max(0, $maxBytes);
        if (strlen($data) <= $maxBytes) {
            return $data;
        }
        $truncated = substr($data, 0, $maxBytes);
        if ((strlen($truncated) % 2) !== 0) {
            $truncated = substr($truncated, 0, -1);
        }
        return $truncated;
    }

    private function encodeUtf16Be(string $text): string
    {
        if (!preg_match('//u', $text)) {
            $text = utf8_encode($text);
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($text, 'UTF-16BE');
        }
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-16BE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }
        $latin1 = utf8_decode($text);
        $out = '';
        $len = strlen($latin1);
        for ($i = 0; $i < $len; $i++) {
            $out .= "\0" . $latin1[$i];
        }
        return $out;
    }

    /**
     * Lädt Mitarbeiter-Informationen herunter (Befehl 0x72)
     */
    public function downloadStaffInfo(): array
    {
        $recordInfo = $this->getRecordInfo();
        $totalUsers = $recordInfo['userAmount'] ?? 0;
        $allStaff = [];
        
        $isFirst = true;
        while ($totalUsers > 0) {
            $num = min(8, $totalUsers);
            $data = chr($isFirst ? 0x01 : 0x00) . chr($num);
            
            try {
                $response = $this->sendCommand(self::CMD_DOWNLOAD_STAFF_INFO, $data);
                $batchData = $response->getData();
                if (empty($batchData)) break;
                
                $count = $batchData[0];
                for ($i = 0; $i < $count; $i++) {
                    $offset = 1 + ($i * 40);
                    $allStaff[] = [
                        'user_id' => ($batchData[$offset] << 32) | ($batchData[$offset+1] << 24) | ($batchData[$offset+2] << 16) | ($batchData[$offset+3] << 8) | $batchData[$offset+4],
                        'pwd' => sprintf('%06x', ($batchData[$offset+5] << 16) | ($batchData[$offset+6] << 8) | $batchData[$offset+7]),
                        'card_id' => ($batchData[$offset+8] << 24) | ($batchData[$offset+9] << 16) | ($batchData[$offset+10] << 8) | $batchData[$offset+11],
                        'name' => trim($this->decodeUtf16Be(implode(array_map('chr', array_slice($batchData, $offset + 12, 20)))), "\0"),
                        'department' => $batchData[$offset+32],
                        'group' => $batchData[$offset+33],
                        'mode' => $batchData[$offset+34],
                        'fp_state' => ($batchData[$offset+35] << 8) | $batchData[$offset+36],
                        'pwd_8_digit' => $batchData[$offset+37],
                        'keep' => $batchData[$offset+38],
                        'special_info' => $batchData[$offset+39]
                    ];
                }
                $totalUsers -= $count;
                if ($count == 0) break;
            } catch (Exception $e) {
                break;
            }
            $isFirst = false;
        }
        return $allStaff;
    }

    public function downloadStaffInfoAll(int $batchSize = 8, int $maxBatches = 64): array
    {
        $batchSize = max(1, min(12, $batchSize));
        $allStaff = [];
        $isFirst = true;
        $lastHash = null;
        $batches = 0;

        while ($batches < $maxBatches) {
            $data = chr($isFirst ? 0x01 : 0x00) . chr($batchSize);

            try {
                $response = $this->sendCommand(self::CMD_DOWNLOAD_STAFF_INFO, $data);
                $batchData = $response->getData();
                if (empty($batchData)) break;

                $count = $batchData[0] ?? 0;
                if ($count <= 0) break;

                $hash = md5(implode(',', $batchData));
                if ($hash === $lastHash) break;
                $lastHash = $hash;

                for ($i = 0; $i < $count; $i++) {
                    $offset = 1 + ($i * 40);
                    $allStaff[] = [
                        'user_id' => ($batchData[$offset] << 32) | ($batchData[$offset+1] << 24) | ($batchData[$offset+2] << 16) | ($batchData[$offset+3] << 8) | $batchData[$offset+4],
                        'pwd' => sprintf('%06x', ($batchData[$offset+5] << 16) | ($batchData[$offset+6] << 8) | $batchData[$offset+7]),
                        'card_id' => ($batchData[$offset+8] << 24) | ($batchData[$offset+9] << 16) | ($batchData[$offset+10] << 8) | $batchData[$offset+11],
                        'name' => trim($this->decodeUtf16Be(implode(array_map('chr', array_slice($batchData, $offset + 12, 20)))), "\0"),
                        'department' => $batchData[$offset+32],
                        'group' => $batchData[$offset+33],
                        'mode' => $batchData[$offset+34],
                        'fp_state' => ($batchData[$offset+35] << 8) | $batchData[$offset+36],
                        'pwd_8_digit' => $batchData[$offset+37],
                        'keep' => $batchData[$offset+38],
                        'special_info' => $batchData[$offset+39]
                    ];
                }

                if ($count < $batchSize) {
                    break;
                }
            } catch (Exception $e) {
                break;
            }

            $isFirst = false;
            $batches++;
        }

        return $allStaff;
    }

    /**
     * Lädt Mitarbeiter-Informationen hoch (Befehl 0x73)
     */
    public function uploadStaffInfo(array $staffList): bool
    {
        $count = min(count($staffList), 8); // Meist auf 8 begrenzt pro Paket
        $data = chr($count);
        
        foreach (array_slice($staffList, 0, $count) as $staff) {
            $id = $staff['user_id'];
            $data .= chr(($id >> 32) & 0xFF) . chr(($id >> 24) & 0xFF) . chr(($id >> 16) & 0xFF) . chr(($id >> 8) & 0xFF) . chr($id & 0xFF);
            
            $pwd = hexdec($staff['pwd'] ?? 'FFFFFF');
            $data .= chr(($pwd >> 16) & 0xFF) . chr(($pwd >> 8) & 0xFF) . chr($pwd & 0xFF);
            
            $card = $staff['card_id'] ?? 0xFFFFFFFF;
            $data .= chr(($card >> 24) & 0xFF) . chr(($card >> 16) & 0xFF) . chr(($card >> 8) & 0xFF) . chr($card & 0xFF);
            
            $name = $this->encodeUtf16Be($staff['name'] ?? '');
            $name = $this->truncateUtf16Be($name, 20);
            $data .= str_pad($name, 20, "\0");
            
            $data .= chr($staff['department'] ?? 0xFF);
            $data .= chr($staff['group'] ?? 0xFF);
            $data .= chr($staff['mode'] ?? 0xFF);
            $data .= chr(0) . chr(0); // FP State
            $data .= chr($staff['pwd_8_digit'] ?? 0xFF);
            $data .= chr($staff['keep'] ?? 0xFF);
            $data .= chr($staff['special_info'] ?? 0xFF);
        }
        
        $response = $this->sendCommand(self::CMD_UPLOAD_STAFF_INFO, $data);
        return $response->isSuccess();
    }

    public function getDeviceInfo(): DeviceInfo
    {
        $info = new DeviceInfo();
        $info->setDeviceId($this->deviceId);

        $this->tryWithReconnect(function () use ($info) {
            $response = $this->sendCommand(self::CMD_GET_INFO_1);
            $info->parseBasic($response->getData());
        }, "Info1 (0x30)");

        $this->tryWithReconnect(function () use ($info) {
            $response = $this->sendCommand(self::CMD_GET_INFO_2);
            $info->parseAdvanced($response->getData());
        }, "Info2 (0x32)");

        $this->tryWithReconnect(function () use ($info) {
            $response = $this->sendCommand(self::CMD_GET_ADVANCED_INFO);
            $info->parseAdvanced2($response->getData());
        }, "Advanced Info (0x34)");

        $this->tryWithReconnect(function () use ($info) {
            $response = $this->sendCommand(self::CMD_GET_RECORD_INFO);
            $info->parseStatistics($response->getData());
        }, "Record Info (0x3C)");

        $this->tryWithReconnect(function () use ($info) {
            $info->setSerialNumber($this->getDeviceSerialNumber());
        }, "Serial Number (0x24)");

        $this->tryWithReconnect(function () use ($info) {
            $info->setProductName($this->getDeviceModel());
        }, "Model (0x48)");

        return $info;
    }

    public function getDateTime(): \DateTime
    {
        $response = $this->sendCommand(self::CMD_GET_DATETIME);
        $data = $response->getData();

        if (count($data) >= 6) {
            $year = 2000 + $data[0];
            $month = $data[1];
            $day = $data[2];
            $hour = $data[3];
            $minute = $data[4];
            $second = $data[5];

            return new \DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d',
                $year, $month, $day, $hour, $minute, $second));
        }

        throw new Exception("Ungültiges Datumsformat");
    }

    public function setDateTime(\DateTime $dateTime): bool
    {
        $year = (int)$dateTime->format('y');
        $month = (int)$dateTime->format('m');
        $day = (int)$dateTime->format('d');
        $hour = (int)$dateTime->format('H');
        $minute = (int)$dateTime->format('i');
        $second = (int)$dateTime->format('s');

        $data = chr($year) . chr($month) . chr($day) 
              . chr($hour) . chr($minute) . chr($second);

        $response = $this->sendCommand(self::CMD_SET_DATETIME, $data);
        return $response->isSuccess();
    }

    public function getNetworkConfig(): NetworkConfig
    {
        $response = $this->sendCommand(self::CMD_GET_NETWORK);
        return NetworkConfig::fromByteArray($response->getData());
    }

    public function getRecordInfo(): array
    {
        $response = $this->sendCommand(self::CMD_GET_RECORD_INFO);
        $data = $response->getData();

        if (count($data) >= 18) {
            return [
                'userAmount' => ($data[0] << 16) | ($data[1] << 8) | $data[2],
                'fingerprintAmount' => ($data[3] << 16) | ($data[4] << 8) | $data[5],
                'passwordAmount' => ($data[6] << 16) | ($data[7] << 8) | $data[8],
                'cardAmount' => ($data[9] << 16) | ($data[10] << 8) | $data[11],
                'totalRecords' => ($data[12] << 16) | ($data[13] << 8) | $data[14],
                'newRecords' => ($data[15] << 16) | ($data[16] << 8) | $data[17]
            ];
        }

        return ['totalRecords' => 0, 'newRecords' => 0];
    }

    public function downloadRecords(int $count = 0, bool $onlyNew = false): array
    {
        $allRecords = [];
        $recordInfo = $this->getRecordInfo();
        $totalToDownload = $onlyNew ? $recordInfo['newRecords'] : $recordInfo['totalRecords'];
        
        if ($count > 0) {
            $totalToDownload = min($totalToDownload, $count);
        }

        if ($totalToDownload <= 0) {
            return [];
        }

        $isFirst = true;
        while ($totalToDownload > 0) {
            $batchSize = min(25, $totalToDownload);
            $type = $onlyNew ? 0x02 : 0x01;
            
            // C# SDK: isFirst ? type : 0x00
            $kind = $isFirst ? $type : 0x00;
            $data = chr($kind) . chr($batchSize);
            
            try {
                $response = $this->sendCommand(self::CMD_DOWNLOAD_RECORDS, $data);
                $batchData = $response->getData();
                
                if (empty($batchData)) break;
                
                // First byte is the counter of records in this packet
                $batchCount = $batchData[0];
                if ($batchCount == 0) break;

                $records = AttendanceRecord::parseMultiple(array_slice($batchData, 1));
                $allRecords = array_merge($allRecords, $records);
                
                $totalToDownload -= $batchCount;
            } catch (Exception $e) {
                Logger::error("Fehler beim Herunterladen der Aufzeichnungen: " . $e->getMessage());
                break;
            }
            
            $isFirst = false;
        }

        return $allRecords;
    }

    /**
     * Fingerabdruck herunterladen (Template)
     * Command: 0x44 (gemäß C# SDK GetFingerprintTemplateCommand)
     */
    public function downloadFingerprint(int $userId, int $fingerIndex): ?string
    {
        // UserID (5 Bytes)
        $data = chr(($userId >> 32) & 0xFF) .
                chr(($userId >> 24) & 0xFF) .
                chr(($userId >> 16) & 0xFF) .
                chr(($userId >> 8) & 0xFF) .
                chr($userId & 0xFF);
        
        // Finger Index (1-based im C# SDK: finger + 1)
        $data .= chr($fingerIndex + 1);

        try {
            // Wir nutzen 0x44 statt 0x40
            $response = $this->sendCommand(0x44, $data);
            return $response->getDataString();
        } catch (Exception $e) {
            Logger::error("Fingerprint download failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Benutzer herunterladen
     * Command: 0x42
     */
    public function downloadUsers(int $startIndex = 0, int $count = 0): array
    {
        $data = chr($startIndex) . chr($count);

        $response = $this->sendCommand(self::CMD_DOWNLOAD_USER, $data);
        return UserInfo::parseMultiple($response->getData());
    }

    public function downloadAllUsers(int $chunkSize = 12): array
    {
        $recordInfo = $this->getRecordInfo();
        $totalUsers = $recordInfo['userAmount'] ?? 0;
        if ($totalUsers <= 0) {
            return [];
        }

        $chunkSize = max(1, min(12, $chunkSize));
        $users = [];
        $start = 0;

        while ($start < $totalUsers) {
            $count = min($chunkSize, $totalUsers - $start);
            try {
                $batch = $this->downloadUsers($start, $count);
            } catch (Exception $e) {
                Logger::warn("Benutzer-Download fehlgeschlagen: " . $e->getMessage());
                break;
            }
            if (empty($batch)) {
                break;
            }
            $users = array_merge($users, $batch);
            $start += $count;
        }

        return $users;
    }

    public function uploadUser(UserInfo $user): bool
    {
        $data = $user->toByteString();
        // Upload user data (CMD_UPLOAD_USER = 0x43)
        $response = $this->sendCommand(self::CMD_UPLOAD_USER, $data);
        return $response->isSuccess();
    }

    public function deleteUser(int $userId): bool
    {
        // UserID (5 Bytes)
        $data = chr(($userId >> 32) & 0xFF) .
                chr(($userId >> 24) & 0xFF) .
                chr(($userId >> 16) & 0xFF) .
                chr(($userId >> 8) & 0xFF) .
                chr($userId & 0xFF);
        
        $response = $this->sendCommand(self::CMD_DELETE_USER, $data);
        return $response->isSuccess();
    }

    public function clearNewRecords(): bool
    {
        // Gemäß C# SDK ClearRecordsCommand.cs: CLEAR_ALL = 0x01
        $data = chr(0x01) . chr(0x00) . chr(0x00) . chr(0x00);
        $response = $this->sendCommand(self::CMD_CLEAR_RECORDS, $data);
        return $response->isSuccess();
    }

    public function clearNewRecordsPartial(int $amount): bool
    {
        // Gemäß C# SDK ClearRecordsCommand.cs: CLEAR_AMOUNT = 0x02
        $data = chr(0x02) . chr(($amount >> 16) & 0xFF) . chr(($amount >> 8) & 0xFF) . chr($amount & 0xFF);
        $response = $this->sendCommand(self::CMD_CLEAR_RECORDS, $data);
        return $response->isSuccess();
    }

    public function clearAllRecords(): bool
    {
        // Gemäß C# SDK ClearRecordsCommand.cs: DELETE_ALL = 0x00
        $data = chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00);
        $response = $this->sendCommand(self::CMD_CLEAR_RECORDS, $data);
        return $response->isSuccess();
    }

    /**
     * Tür entriegeln (Befehl 0x5E)
     */
    public function unlockDoor(): bool
    {
        $response = $this->sendCommand(0x5E);
        return $response->isSuccess();
    }

    /**
     * Fingerabdruck-Registrierung starten (Befehl 0x5C)
     * @param int $userId
     * @param bool $isFirst Erster Versuch (true) oder Folgerversuch (false)
     */
    public function enrollFingerprint(int $userId, bool $isFirst = true): bool
    {
        $data = chr(($userId >> 32) & 0xFF) .
                chr(($userId >> 24) & 0xFF) .
                chr(($userId >> 16) & 0xFF) .
                chr(($userId >> 8) & 0xFF) .
                chr($userId & 0xFF);
        
        $data .= chr(0x01); // Finger Index 1
        $data .= chr($isFirst ? 0x00 : 0x01);
        
        $response = $this->sendCommand(self::CMD_ENROLL_FINGERPRINT, $data);
        return $response->isSuccess();
    }

    /**
     * Karte einlesen/abfragen (Befehl 0x7E - Inquire Card)
     * Wartet bis eine Karte am Gerät präsentiert wird.
     */
    public function inquireCard(int $retries = 5): int
    {
        while ($retries-- > 0) {
            try {
                $response = $this->sendCommand(0x7E);
                $data = $response->getData();
                if (!empty($data)) {
                    $cardId = 0;
                    for ($i = 0; $i < count($data); $i++) {
                        $cardId = ($cardId << 8) | $data[$i];
                    }
                    if ($cardId !== 0) return $cardId;
                }
            } catch (Exception $e) {
                // Timeout oder kein Erfolg, ignorieren und weitermachen
            }
            usleep(500000); // 500ms warten
        }
        return 0;
    }

    /**
     * Biometrie-Typ des Geräts abrufen (Befehl 0x48)
     */
    public function getDeviceBiometricType(): string
    {
        $response = $this->sendCommand(self::CMD_GET_MODEL);
        $data = $response->getDataString();
        return trim($data);
    }

    /**
     * Fingerabdruck auf das Gerät hochladen (Template)
     * Command: 0x45
     */
    public function uploadFingerprint(int $userId, int $fingerIndex, string $template): bool
    {
        // UserID (5 Bytes)
        $data = chr(($userId >> 32) & 0xFF) .
                chr(($userId >> 24) & 0xFF) .
                chr(($userId >> 16) & 0xFF) .
                chr(($userId >> 8) & 0xFF) .
                chr($userId & 0xFF);
        
        // Finger Index (1-based im C# SDK: finger + 1)
        $data .= chr($fingerIndex + 1);

        // Template (338 Bytes Daten, insgesamt 344 mit ID/Index)
        $data .= str_pad(substr($template, 0, 338), 338, "\0");

        $response = $this->sendCommand(0x45, $data);
        return $response->isSuccess();
    }

    /**
     * Gesicht auf das Gerät hochladen (Template)
     * Command: 0x45
     */
    public function uploadFace(int $userId, string $template): bool
    {
        // UserID (5 Bytes)
        $data = chr(($userId >> 32) & 0xFF) .
                chr(($userId >> 24) & 0xFF) .
                chr(($userId >> 16) & 0xFF) .
                chr(($userId >> 8) & 0xFF) .
                chr($userId & 0xFF);
        
        $data .= chr(0x01); // Index 1 für Gesicht

        // Template (C# SDK: 15360 Bytes)
        $data .= str_pad(substr($template, 0, 15360), 15360, "\0");

        $response = $this->sendCommand(0x45, $data);
        return $response->isSuccess();
    }

    /**
     * Datensatz auf das Gerät schreiben (CMD_SET_RECORDS = 0x41)
     */
    public function uploadRecord(AttendanceRecord $record): bool
    {
        $response = $this->sendCommand(0x41, $record->toByteString());
        return $response->isSuccess();
    }

    /**
     * Alle Mitarbeiterdaten vom Gerät löschen (Befehl 0x4D)
     */
    public function deleteAllEmployees(): bool
    {
        $data = str_repeat("\0", 5) . chr(0xFF);
        $response = $this->sendCommand(0x4D, $data);
        return $response->isSuccess();
    }

    /**
     * Spezifische Verifikationsdaten eines Mitarbeiters löschen (Befehl 0x4C)
     * @param int $userId
     * @param int $method 0xFF für alle Daten, oder spezifischer Backup-Code
     */
    public function deleteEmployeeData(int $userId, int $method = 0xFF): bool
    {
        $data = chr(($userId >> 32) & 0xFF) .
                chr(($userId >> 24) & 0xFF) .
                chr(($userId >> 16) & 0xFF) .
                chr(($userId >> 8) & 0xFF) .
                chr($userId & 0xFF);
        
        $data .= chr($method);

        $response = $this->sendCommand(0x4C, $data);
        return $response->isSuccess();
    }

    public function getDeviceModel(): string
    {
        $response = $this->sendCommand(self::CMD_GET_MODEL);
        $data = $response->getDataString();

        return trim($data);
    }

    /**
     * Holt erweiterte Geräteinformationen
     * Command: 0x34
     */
    public function getAdvancedDeviceInfo(): array
    {
        $response = $this->sendCommand(self::CMD_GET_ADVANCED_INFO);
        $data = $response->getData();

        if (count($data) >= 20) {
            return [
                'totalUsers' => ($data[0] << 16) | ($data[1] << 8) | $data[2],
                'totalFingerprints' => ($data[3] << 16) | ($data[4] << 8) | $data[5],
                'totalPasswords' => ($data[6] << 16) | ($data[7] << 8) | $data[8],
                'totalCards' => ($data[9] << 16) | ($data[10] << 8) | $data[11],
                'totalAttendanceRecords' => ($data[12] << 16) | ($data[13] << 8) | $data[14],
                'totalNewRecords' => ($data[15] << 16) | ($data[16] << 8) | $data[17],
            ];
        }

        return [];
    }

    /**
     * Holt Geräteserialnummer
     * Command: 0x24
     */
    public function getDeviceSerialNumber(): string
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_SN);
        $data = $response->getData();

        return BinaryHelper::readFixedString($data, 0, count($data));
    }

    /**
     * Setzt Geräteserialnummer
     * Command: 0x25
     */
    public function setDeviceSerialNumber(string $serialNumber): bool
    {
        $data = str_pad($serialNumber, 16, "\0");
        $data = substr($data, 0, 16);
        $response = $this->sendCommand(self::CMD_SET_DEVICE_SN, $data);
        return $response->isSuccess();
    }

    /**
     * Holt TCP/IP Parameter
     * Command: 0x3A
     */
    public function getTcpIpParams(): array
    {
        $response = $this->sendCommand(self::CMD_GET_NETWORK);
        $data = $response->getData();

        if (count($data) >= 27) {
            return [
                'ipAddress' => sprintf('%d.%d.%d.%d', $data[0], $data[1], $data[2], $data[3]),
                'subnetMask' => sprintf('%d.%d.%d.%d', $data[4], $data[5], $data[6], $data[7]),
                'macAddress' => sprintf('%02X:%02X:%02X:%02X:%02X:%02X', $data[8], $data[9], $data[10], $data[11], $data[12], $data[13]),
                'gateway' => sprintf('%d.%d.%d.%d', $data[14], $data[15], $data[16], $data[17]),
                'serverIp' => sprintf('%d.%d.%d.%d', $data[18], $data[19], $data[20], $data[21]),
                'farLimit' => $data[22],
                'comPort' => ($data[23] << 8) | $data[24],
                'tcpMode' => $data[25],
                'dhcp' => (bool)$data[26]
            ];
        }

        return [];
    }

    /**
     * Setzt TCP/IP Parameter
     * Command: 0x3B
     */
    public function setTcpIpParams(array $config): bool
    {
        $data = '';
        foreach (['ipAddress', 'subnetMask', 'macAddress', 'gateway', 'serverIp'] as $key) {
            $addr = $config[$key];
            if ($key === 'macAddress') {
                $octets = explode(':', $addr);
                foreach ($octets as $octet) $data .= chr(hexdec($octet));
            } else {
                $octets = explode('.', $addr);
                foreach ($octets as $octet) $data .= chr((int)$octet);
            }
        }
        $data .= chr($config['farLimit'] ?? 0);
        $port = $config['comPort'] ?? 5010;
        $data .= chr(($port >> 8) & 0xFF) . chr($port & 0xFF);
        $data .= chr($config['tcpMode'] ?? 0);
        $data .= chr(($config['dhcp'] ?? false) ? 1 : 0);

        $response = $this->sendCommand(self::CMD_SET_NETWORK, $data);
        return $response->isSuccess();
    }

    /**
     * Nur neue Records herunterladen
     * Command: 0x74
     */
    public function downloadNewRecords(): array
    {
        $response = $this->sendCommand(self::CMD_DOWNLOAD_NEW_RECORDS);
        return AttendanceRecord::parseMultiple($response->getData());
    }

    /**
     * Tür öffnen (Befehl 0x5E)
     */
    public function openDoor(): bool
    {
        $response = $this->sendCommand(self::CMD_UNLOCK_DOOR);
        return $response->isSuccess();
    }

    /**
     * Gerät neustarten
     * Command: 0x8B
     */
    public function rebootDevice(): bool
    {
        $response = $this->sendCommand(self::CMD_REBOOT_DEVICE);
        return $response->isSuccess();
    }

    /**
     * Werkseinstellungen zurücksetzen
     * Command: 0x8D
     */
    public function factoryReset(): bool
    {
        $response = $this->sendCommand(self::CMD_FACTORY_RESET);
        return $response->isSuccess();
    }

    /**
     * Timezone-Informationen abrufen
     * Command: 0x50 (GetTimeZoneInfoCommand)
     */
    public function getTimezoneInfo(int $number): array
    {
        $response = $this->sendCommand(0x50, chr($number));
        $data = $response->getData();
        $days = [];
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        for ($day = 0; $day < 7; $day++) {
            $offset = $day * 4;
            if ($offset + 3 < count($data)) {
                $days[$dayNames[$day]] = [
                    'from' => sprintf('%02d:%02d', $data[$offset], $data[$offset+1]),
                    'to' => sprintf('%02d:%02d', $data[$offset+2], $data[$offset+3])
                ];
            }
        }
        return $days;
    }

    /**
     * Timezone setzen
     * Command: 0x51 (SetTimeZoneInfoCommand)
     */
    public function setTimezoneInfo(int $number, array $days): bool
    {
        $data = chr($number);
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        foreach ($dayNames as $name) {
            $day = $days[$name] ?? ['from' => '00:00', 'to' => '00:00'];
            list($fH, $fM) = explode(':', $day['from']);
            list($tH, $tM) = explode(':', $day['to']);
            $data .= chr((int)$fH) . chr((int)$fM) . chr((int)$tH) . chr((int)$tM);
        }
        
        $response = $this->sendCommand(0x51, $data);
        return $response->isSuccess();
    }

    /**
     * Klingelplan abrufen
     * Command: 0x54 (GetScheduledBellsCommand)
     */
    public function getBellSchedule(): BellSchedule
    {
        $response = $this->sendCommand(self::CMD_GET_BELL_SCHEDULE);
        return BellSchedule::fromByteArray($response->getData());
    }

    /**
     * Klingelplan setzen
     * Command: 0x55 (SetScheduledBellCommand)
     */
    public function setBellSchedule(int $slot, int $hour, int $minute, int $daysMask): bool
    {
        if ($slot < 1 || $slot > 30) {
            throw new \Exception("Slot must be between 1 and 30");
        }
        $data = chr($slot) . chr($hour) . chr($minute) . chr($daysMask);
        $response = $this->sendCommand(self::CMD_SET_BELL_SCHEDULE, $data);
        return $response->isSuccess();
    }

    /**
     * Holt Geräte-ID
     * Command: 0x46
     */
    public function getDeviceIdCommand(): int
    {
        $response = $this->sendCommand(self::CMD_GET_DEVICE_ID);
        $data = $response->getData();
        if (count($data) >= 4) {
            return ($data[0] << 24) | ($data[1] << 16) | ($data[2] << 8) | $data[3];
        }
        return 0;
    }

    /**
     * Setzt Geräte-ID
     * Command: 0x47
     */
    public function setDeviceIdCommand(int $newDeviceId): bool
    {
        $data = chr(($newDeviceId >> 24) & 0xFF) . 
                chr(($newDeviceId >> 16) & 0xFF) . 
                chr(($newDeviceId >> 8) & 0xFF) . 
                chr($newDeviceId & 0xFF);
        $response = $this->sendCommand(self::CMD_SET_DEVICE_ID, $data);
        if ($response->isSuccess()) {
            $this->deviceId = $newDeviceId;
            return true;
        }
        return false;
    }

    /**
     * Einzelnen Datensatz löschen
     * Command: 0x4E
     */
    /**
     * Setzt das Verbindungs-Passwort (CMD 0x04)
     */
    public function setConnectionPassword(string $user = "admin", string $password = "12345"): bool
    {
        // Das Passwort muss genau 12 Bytes haben (mit Null-Bytes aufgefüllt)
        // Der User ebenfalls 12 Bytes.
        $data = str_pad($user, 12, "\0") . str_pad($password, 12, "\0");
        try {
            // Wir nutzen hier direkt sendCommand, was nun korrekt (ohne RET vor Length) sendet
            $response = $this->sendCommand(self::CMD_SET_CONNECTION_PASSWORD, $data);
            
            // Die Antwort auf 0x04 ist ACK 0x84 (0x04 + 0x80) mit RET 0x00
            return $response->getRet() === 0;
        } catch (Exception $e) {
            Logger::error("Authentifizierung fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Startet einen Verbindungs-Handshake (CMD 0x01)
     */
    public function handshake(): bool
    {
        return $this->ping();
    }
}

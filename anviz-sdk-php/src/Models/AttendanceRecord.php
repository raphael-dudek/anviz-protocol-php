<?php

namespace Anviz\SDK\Models;

use Anviz\SDK\Utils\BinaryHelper;
use DateTime;

/**
 * Anwesenheitsaufzeichnungs-Modell
 */
class AttendanceRecord
{
    private $userId;
    private $timestamp;
    private $backupCode;
    private $recordType;
    private $workType;
    private $reserved;

    /**
     * Anviz Epoch: 2000-01-02 00:00:00
     */
    const EPOCH_START = 946771200; // strtotime('2000-01-02 00:00:00 UTC')
    const ANVIZ_EPOCH_PHPANVIZ = 946764000; // PHPAnviz nutzt 2000-01-02 aber mit anderem Timestamp?

    public function __construct()
    {
        $this->userId = 0;
        $this->timestamp = new DateTime();
        $this->backupCode = 0;
        $this->recordType = 0;
        $this->workType = 0;
        $this->reserved = 0;
    }

    public static function fromByteArray(array $data, int $offset = 0): self
    {
        $record = new self();

        // User ID (5 Bytes)
        $record->userId = (
            ($data[$offset] << 32) | 
            ($data[$offset+1] << 24) | 
            ($data[$offset+2] << 16) | 
            ($data[$offset+3] << 8) | 
            $data[$offset+4]
        );
        $offset += 5;

        // Timestamp (4 Bytes Big-Endian)
        // Anviz nutzt Sekunden seit 2000-01-02
        $secondsSinceEpoch = BinaryHelper::readUInt32BE($data, $offset);
        $record->timestamp = new DateTime('@' . (self::EPOCH_START + $secondsSinceEpoch));
        $offset += 4;

        $record->backupCode = $data[$offset] ?? 0;
        $offset += 1;

        // Record Type (1 Byte) - Bit 7 indicates door open flag, low 4 bits are attendance state
        $record->recordType = $data[$offset] ?? 0;
        $offset += 1;

        // Work Type (2 Bytes)
        $record->workType = (($data[$offset] ?? 0) << 8) | ($data[$offset + 1] ?? 0);
        $offset += 2;

        // Reserved (1 Byte)
        $record->reserved = $data[$offset] ?? 0;

        return $record;
    }

    public static function parseMultiple(array $data): array
    {
        $records = [];
        $recordSize = 14; // 5 + 4 + 1 + 1 + 3 = 14
        $count = (int)(count($data) / $recordSize);

        for ($i = 0; $i < $count; $i++) {
            $offset = $i * $recordSize;
            if ($offset + $recordSize <= count($data)) {
                $records[] = self::fromByteArray($data, $offset);
            }
        }

        return $records;
    }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): void { $this->userId = $userId; }

    public function getTimestamp(): DateTime { return $this->timestamp; }
    public function setTimestamp(DateTime $timestamp): void { $this->timestamp = $timestamp; }

    public function getBackupCode(): int { return $this->backupCode; }
    public function setBackupCode(int $backupCode): void { $this->backupCode = $backupCode; }

    public function getRecordType(): int { return $this->recordType; }
    public function setRecordType(int $recordType): void { $this->recordType = $recordType; }

    public function getWorkType(): int { return $this->workType; }
    public function setWorkType(int $workType): void { $this->workType = $workType; }

    /**
     * Konvertiert das Modell in einen Byte-String für das Gerät (14 Bytes)
     */
    public function toByteString(): string
    {
        $data = '';

        // User ID (5 Bytes)
        $id = $this->userId;
        $data .= chr(($id >> 32) & 0xFF);
        $data .= chr(($id >> 24) & 0xFF);
        $data .= chr(($id >> 16) & 0xFF);
        $data .= chr(($id >> 8) & 0xFF);
        $data .= chr($id & 0xFF);

        // Timestamp (4 Bytes Big-Endian)
        $secondsSinceEpoch = $this->timestamp->getTimestamp() - self::EPOCH_START;
        $data .= chr(($secondsSinceEpoch >> 24) & 0xFF);
        $data .= chr(($secondsSinceEpoch >> 16) & 0xFF);
        $data .= chr(($secondsSinceEpoch >> 8) & 0xFF);
        $data .= chr($secondsSinceEpoch & 0xFF);

        $data .= chr($this->backupCode);
        $data .= chr($this->recordType);

        // Work Type (2 Bytes)
        $data .= chr(($this->workType >> 8) & 0xFF);
        $data .= chr($this->workType & 0xFF);

        // Reserved (1 Byte)
        $data .= chr($this->reserved & 0xFF);

        return $data;
    }

    public function getVerificationMethod(): string
    {
        $methods = [];

        if ($this->backupCode & 0x01) {
            $methods[] = 'Fingerprint 1';
        }
        if ($this->backupCode & 0x02) {
            $methods[] = 'Fingerprint 2';
        }
        if ($this->backupCode & 0x04) {
            $methods[] = 'Password';
        }
        if ($this->backupCode & 0x08) {
            $methods[] = 'Card';
        }
        if ($this->backupCode & 0x10) {
            $methods[] = 'Face';
        }
        if ($this->backupCode & 0x20) {
            $methods[] = 'Palm';
        }

        if (!empty($methods)) {
            return implode('+', $methods);
        }

        return 'Unknown';
    }

    public function getAttendanceState(): int
    {
        return $this->recordType & 0x0F;
    }

    public function isDoorOpen(): bool
    {
        return ($this->recordType & 0x80) !== 0;
    }

    public function getRecordTypeName(array $stateNames = []): string
    {
        $state = $this->getAttendanceState();
        $name = $stateNames[$state] ?? "State $state";
        if ($this->isDoorOpen()) {
            return $name . ' (Door)';
        }
        return $name;
    }
}

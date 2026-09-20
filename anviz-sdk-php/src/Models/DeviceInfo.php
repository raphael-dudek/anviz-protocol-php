<?php

namespace Anviz\SDK\Models;

use Anviz\SDK\Utils\BinaryHelper;

/**
 * Geräteinformations-Modell
 */
class DeviceInfo
{
    private $deviceId;
    private $firmwareVersion;
    private $serialNumber;
    private $productName;
    private $userCapacity;
    private $fingerprintCapacity;
    private $cardCapacity;
    private $recordCapacity;
    private $passwordCapacity;
    private $faceCapacity;
    private $palmCapacity;
    private $language;
    private $timezone;
    private $dateFormat;
    private $timeFormat;
    private $lockDelay;
    private $voicePrompt;
    private $volume;
    private $password;
    private $sleepTime;
    private $attendanceState;
    private $languageFlag;
    private $commandVersion;

    // Advanced Info (0x32)
    private $comparisonPrecision;
    private $wiegandHead;
    private $wiegandOption;
    private $workCodePermission;
    private $realTimeMode;
    private $fpAutoUpdate;
    private $relayMode;
    private $memoryFullAlarm;
    private $repeatAttendanceDelay;
    private $doorSensorDelay;
    private $scheduledBellDelay;

    // Statistics (0x3C)
    private $userAmount;
    private $fingerprintAmount;
    private $passwordAmount;
    private $cardAmount;
    private $totalRecords;
    private $newRecords;

    public static function fromByteArray(array $data): self
    {
        $info = new self();
        $info->parseBasic($data);
        return $info;
    }

    public function parseBasic(array $data): void
    {
        if (count($data) >= 18) {
            $this->firmwareVersion = BinaryHelper::readFixedString($data, 0, 8);
            $this->password = BinaryHelper::decodePassword(array_slice($data, 8, 3));
            $this->sleepTime = $data[11];
            $this->volume = $data[12];
            $this->language = $data[13];
            $this->dateFormat = $data[14] & 0xFE;
            $this->timeFormat = ($data[14] & 0x01) === 0 ? 24 : 12;
            $this->attendanceState = $data[15];
            $this->languageFlag = $data[16];
            $this->commandVersion = $data[17];
        }
    }

    public function parseAdvanced(array $data): void
    {
        if (count($data) >= 14) {
            $this->comparisonPrecision = $data[0];
            $this->wiegandHead = $data[1];
            $this->wiegandOption = $data[2];
            $this->workCodePermission = $data[3] > 0;
            $this->realTimeMode = $data[4] > 0;
            $this->fpAutoUpdate = $data[5] > 0;
            $this->relayMode = $data[6];
            $this->lockDelay = $data[7];
            // C# liest 3 Bytes für MemoryFullAlarm
            $this->memoryFullAlarm = ($data[8] << 16) | ($data[9] << 8) | $data[10];
            $this->repeatAttendanceDelay = $data[11];
            $this->doorSensorDelay = $data[12];
            $this->scheduledBellDelay = $data[13];
        }
    }

    public function parseAdvanced2(array $data): void
    {
        if (count($data) >= 18) {
            $this->userCapacity = ($data[0] << 16) | ($data[1] << 8) | $data[2];
            $this->fingerprintCapacity = ($data[3] << 16) | ($data[4] << 8) | $data[5];
            $this->passwordCapacity = ($data[6] << 16) | ($data[7] << 8) | $data[8];
            $this->cardCapacity = ($data[9] << 16) | ($data[10] << 8) | $data[11];
            $this->recordCapacity = ($data[12] << 16) | ($data[13] << 8) | $data[14];
            // 15-17 scheint totalNewRecords zu sein in getAdvancedDeviceInfo
        }
    }

    public function parseStatistics(array $data): void
    {
        if (count($data) >= 18) {
            $this->userAmount = ($data[0] << 16) | ($data[1] << 8) | $data[2];
            $this->fingerprintAmount = ($data[3] << 16) | ($data[4] << 8) | $data[5];
            $this->passwordAmount = ($data[6] << 16) | ($data[7] << 8) | $data[8];
            $this->cardAmount = ($data[9] << 16) | ($data[10] << 8) | $data[11];
            $this->totalRecords = ($data[12] << 16) | ($data[13] << 8) | $data[14];
            $this->newRecords = ($data[15] << 16) | ($data[16] << 8) | $data[17];
        }
    }

    public function getDeviceId(): int { return $this->deviceId ?? 0; }
    public function setDeviceId(int $id): void { $this->deviceId = $id; }
    public function getFirmwareVersion(): string { return $this->firmwareVersion ?? ''; }
    public function getSerialNumber(): string { return $this->serialNumber ?? ''; }
    public function setSerialNumber(string $sn): void { $this->serialNumber = $sn; }
    public function getProductName(): string { return $this->productName ?? ''; }
    public function setProductName(string $name): void { $this->productName = $name; }
    
    public function getUserAmount(): int { return $this->userAmount ?? 0; }
    public function getFingerprintAmount(): int { return $this->fingerprintAmount ?? 0; }
    public function getTotalRecords(): int { return $this->totalRecords ?? 0; }
    public function getNewRecords(): int { return $this->newRecords ?? 0; }
    public function getRecordCapacity(): int { return $this->recordCapacity ?? 0; }
    public function setRecordCapacity(int $cap): void { $this->recordCapacity = $cap; }

    public function getUserCapacity(): int { return $this->userCapacity ?? 0; }
    public function setUserCapacity(int $cap): void { $this->userCapacity = $cap; }

    public function getFingerprintCapacity(): int { return $this->fingerprintCapacity ?? 0; }
    public function setFingerprintCapacity(int $cap): void { $this->fingerprintCapacity = $cap; }

    public function getCardCapacity(): int { return $this->cardCapacity ?? 0; }
    public function setCardCapacity(int $cap): void { $this->cardCapacity = $cap; }

    public function getFaceCapacity(): int { return $this->faceCapacity ?? 0; }
    public function setFaceCapacity(int $cap): void { $this->faceCapacity = $cap; }

    public function getPalmCapacity(): int { return $this->palmCapacity ?? 0; }
    public function setPalmCapacity(int $cap): void { $this->palmCapacity = $cap; }

    public function getVolume(): int { return $this->volume ?? 0; }
    public function getSleepTime(): int { return $this->sleepTime ?? 0; }
    public function getLockDelay(): int { return $this->lockDelay ?? 0; }

    /**
     * Gibt alle Informationen als Array zurück
     */
    public function toArray(): array
    {
        return [
            'deviceId' => $this->getDeviceId(),
            'firmwareVersion' => $this->getFirmwareVersion(),
            'serialNumber' => $this->getSerialNumber(),
            'productName' => $this->getProductName(),
            'userAmount' => $this->getUserAmount(),
            'fingerprintAmount' => $this->getFingerprintAmount(),
            'totalRecords' => $this->getTotalRecords(),
            'newRecords' => $this->getNewRecords(),
            'volume' => $this->getVolume(),
            'sleepTime' => $this->getSleepTime(),
            'lockDelay' => $this->getLockDelay(),
            'timeFormat' => $this->timeFormat
        ];
    }
}

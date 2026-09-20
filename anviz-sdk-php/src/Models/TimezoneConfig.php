<?php

namespace Anviz\SDK\Models;

use Anviz\SDK\Utils\BinaryHelper;

/**
 * Timezone-Konfigurationsdaten für Anviz-Geräte
 * Basierend auf SDK Dokumentation Commands 0xB0/0xB1
 */
class TimezoneConfig
{
    private int $timezoneOffset;
    private bool $daylightSaving;
    private int $dstStartMonth;
    private int $dstStartWeek;
    private int $dstStartDay;
    private int $dstStartHour;
    private int $dstEndMonth;
    private int $dstEndWeek;
    private int $dstEndDay;
    private int $dstEndHour;

    public static function fromByteArray(array $data): self
    {
        $config = new self();

        if (count($data) >= 10) {
            $config->timezoneOffset = $data[0];
            if ($config->timezoneOffset > 127) {
                $config->timezoneOffset -= 256; // Vorzeichenbehandlung
            }

            $config->daylightSaving = (bool)$data[1];
            $config->dstStartMonth = $data[2];
            $config->dstStartWeek = $data[3];
            $config->dstStartDay = $data[4];
            $config->dstStartHour = $data[5];
            $config->dstEndMonth = $data[6];
            $config->dstEndWeek = $data[7];
            $config->dstEndDay = $data[8];
            $config->dstEndHour = $data[9];
        }

        return $config;
    }

    public function toByteArray(): array
    {
        $offset = $this->timezoneOffset;
        if ($offset < 0) $offset += 256;

        return [
            $offset,
            $this->daylightSaving ? 1 : 0,
            $this->dstStartMonth,
            $this->dstStartWeek,
            $this->dstStartDay,
            $this->dstStartHour,
            $this->dstEndMonth,
            $this->dstEndWeek,
            $this->dstEndDay,
            $this->dstEndHour
        ];
    }

    public function getTimezoneOffset(): int { return $this->timezoneOffset; }
    public function isDaylightSaving(): bool { return $this->daylightSaving; }
    public function getDstStartMonth(): int { return $this->dstStartMonth; }
    public function getDstStartWeek(): int { return $this->dstStartWeek; }
    public function getDstStartDay(): int { return $this->dstStartDay; }
    public function getDstStartHour(): int { return $this->dstStartHour; }
    public function getDstEndMonth(): int { return $this->dstEndMonth; }
    public function getDstEndWeek(): int { return $this->dstEndWeek; }
    public function getDstEndDay(): int { return $this->dstEndDay; }
    public function getDstEndHour(): int { return $this->dstEndHour; }

    public function setTimezoneOffset(int $offset): void { $this->timezoneOffset = $offset; }
    public function setDaylightSaving(bool $enabled): void { $this->daylightSaving = $enabled; }
    public function setDstStartMonth(int $month): void { $this->dstStartMonth = $month; }
    public function setDstStartWeek(int $week): void { $this->dstStartWeek = $week; }
    public function setDstStartDay(int $day): void { $this->dstStartDay = $day; }
    public function setDstStartHour(int $hour): void { $this->dstStartHour = $hour; }
    public function setDstEndMonth(int $month): void { $this->dstEndMonth = $month; }
    public function setDstEndWeek(int $week): void { $this->dstEndWeek = $week; }
    public function setDstEndDay(int $day): void { $this->dstEndDay = $day; }
    public function setDstEndHour(int $hour): void { $this->dstEndHour = $hour; }

    public function getTimezoneName(): string
    {
        $names = [
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
            0 => 'UTC+0 (London/GMT)',
            1 => 'UTC+1 (Berlin/CET)',
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

        return $names[$this->timezoneOffset] ?? "UTC{$this->timezoneOffset}";
    }
}

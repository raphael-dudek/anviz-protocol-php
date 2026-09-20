<?php

namespace Anviz\SDK\Models;

/**
 * Klingelplan-Konfiguration für Anviz-Geräte
 * Basierend auf SDK Dokumentation Commands 0xB2/0xB3
 */
class BellSchedule
{
    private array $schedules = [];

    public function __construct()
    {
        // Initialisiere mit 30 leeren Zeitslots (Anviz Standard)
        for ($i = 0; $i < 30; $i++) {
            $this->schedules[$i] = [
                'hour' => 0,
                'minute' => 0,
                'days' => 0,
                'enabled' => false
            ];
        }
    }

    public static function fromByteArray(array $data): self
    {
        $schedule = new self();
        $schedule->schedules = [];

        $recordLength = 3;
        $count = min(30, (int)(count($data) / $recordLength));
        
        for ($i = 0; $i < $count; $i++) {
            $offset = $i * $recordLength;
            $days = $data[$offset + 2];
            $schedule->schedules[$i] = [
                'hour' => $data[$offset],
                'minute' => $data[$offset + 1],
                'days' => $days,
                'enabled' => $days != 0 && $days != 0xFF
            ];
        }

        return $schedule;
    }

    public function toByteArray(): array
    {
        // Diese Methode wird normalerweise pro Slot aufgerufen mit einer Slot-Nummer (1-30)
        // Das C# SDK SetScheduledBellCommand sendet [number, hour, minute, days]
        return []; 
    }

    public function setSchedule(int $index, int $hour, int $minute, int $melody = 0, int $volume = 50, bool $enabled = true): void
    {
        if ($index >= 0 && $index < 8) {
            $this->schedules[$index] = [
                'hour' => $hour,
                'minute' => $minute,
                'melody' => $melody,
                'volume' => $volume,
                'enabled' => $enabled,
                'days' => 0x7F, // Alle Wochentage
                'duration' => 1
            ];
        }
    }

    public function getSchedule(int $index): ?array
    {
        return $this->schedules[$index] ?? null;
    }

    public function getAllSchedules(): array
    {
        return $this->schedules;
    }

    public function enableSchedule(int $index, bool $enabled = true): void
    {
        if (isset($this->schedules[$index])) {
            $this->schedules[$index]['enabled'] = $enabled;
        }
    }

    public function setVolume(int $index, int $volume): void
    {
        if (isset($this->schedules[$index])) {
            $this->schedules[$index]['volume'] = max(0, min(100, $volume));
        }
    }

    public function setDays(int $index, int $daysMask): void
    {
        if (isset($this->schedules[$index])) {
            $this->schedules[$index]['days'] = $daysMask & 0x7F;
        }
    }

    /**
     * Hilfsfunktion zur Konvertierung der Tage-Bitmaske in Wochentage
     */
    public function getDayNames(int $daysMask): array
    {
        $days = [];
        $dayNames = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];

        for ($i = 0; $i < 7; $i++) {
            if ($daysMask & (1 << $i)) {
                $days[] = $dayNames[$i];
            }
        }

        return $days;
    }
}

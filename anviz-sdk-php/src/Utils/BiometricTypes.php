<?php

namespace Anviz\SDK\Utils;

/**
 * Hilfsklasse zur Bestimmung des biometrischen Typs basierend auf Modellcodes
 */
class BiometricTypes
{
    const UNKNOWN     = 0;
    const FINGERPRINT = 1;
    const FACE        = 2;
    const CARD        = 3;
    const PALM        = 4;

    /**
     * Dekodiert den Modell-String in einen BiometricType
     */
    public static function decodeBiometricType(string $modelCode): int
    {
        $modelCode = strtoupper(trim($modelCode));
        
        if (strpos($modelCode, 'FACE') !== false || strpos($modelCode, 'EP300') !== false) {
            return self::FACE;
        }
        
        if (strpos($modelCode, 'TC550') !== false || strpos($modelCode, 'M7') !== false || strpos($modelCode, 'M5') !== false) {
            return self::FINGERPRINT;
        }
        
        if (strpos($modelCode, 'OC500') !== false || strpos($modelCode, 'W1') !== false) {
            return self::CARD;
        }

        return self::FINGERPRINT; // Default für die meisten Anviz Geräte
    }

    public static function getTypeName(int $type): string
    {
        $names = [
            self::UNKNOWN     => 'Unbekannt',
            self::FINGERPRINT => 'Fingerabdruck',
            self::FACE        => 'Gesichtserkennung',
            self::CARD        => 'RFID Karte',
            self::PALM        => 'Handflächenerkennung',
        ];
        return $names[$type] ?? 'Unbekannt';
    }
}

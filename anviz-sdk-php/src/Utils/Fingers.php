<?php

namespace Anviz\SDK\Utils;

/**
 * Hilfsklasse für Finger-Indizes und Bitmasken
 */
class Fingers
{
    const FINGER_RIGHT_THUMB  = 0;
    const FINGER_RIGHT_INDEX  = 1;
    const FINGER_RIGHT_MIDDLE = 2;
    const FINGER_RIGHT_ANULAR = 3;
    const FINGER_RIGHT_LITTLE = 4;
    const FINGER_LEFT_LITTLE  = 5;
    const FINGER_LEFT_ANULAR  = 6;
    const FINGER_LEFT_MIDDLE  = 7;
    const FINGER_LEFT_INDEX   = 8;
    const FINGER_LEFT_THUMB   = 9;

    /**
     * Dekodiert die 2-Byte Finger-Bitmaske in eine Liste von Indizes
     */
    public static function decodeFingers(int $value): array
    {
        $ret = [];
        for ($i = 0; $i < 10; $i++) {
            if (($value & (1 << $i)) !== 0) {
                $ret[] = $i;
            }
        }
        return $ret;
    }

    /**
     * Enkodiert eine Liste von Finger-Indizes in eine 2-Byte Bitmaske
     */
    public static function encodeFingers(array $fingers): int
    {
        $ret = 0;
        foreach ($fingers as $f) {
            $ret |= (1 << (int)$f);
        }
        return $ret;
    }

    /**
     * Gibt den Namen eines Fingers zurück
     */
    public static function getFingerName(int $finger): string
    {
        $names = [
            self::FINGER_RIGHT_THUMB  => 'Rechter Daumen',
            self::FINGER_RIGHT_INDEX  => 'Rechter Zeigefinger',
            self::FINGER_RIGHT_MIDDLE => 'Rechter Mittelfinger',
            self::FINGER_RIGHT_ANULAR => 'Rechter Ringfinger',
            self::FINGER_RIGHT_LITTLE => 'Rechter kleiner Finger',
            self::FINGER_LEFT_LITTLE  => 'Linker kleiner Finger',
            self::FINGER_LEFT_ANULAR  => 'Linker Ringfinger',
            self::FINGER_LEFT_MIDDLE  => 'Linker Mittelfinger',
            self::FINGER_LEFT_INDEX   => 'Linker Zeigefinger',
            self::FINGER_LEFT_THUMB   => 'Linker Daumen',
        ];
        return $names[$finger] ?? 'Unbekannt';
    }
}

<?php

namespace Anviz\SDK\Utils;

/**
 * Hilfsfunktionen für Binärdaten
 */
class BinaryHelper
{
    public static function readUInt16LE(array $data, int $offset): int
    {
        return $data[$offset] | ($data[$offset + 1] << 8);
    }

    public static function readUInt16BE(array $data, int $offset): int
    {
        return ($data[$offset] << 8) | $data[$offset + 1];
    }

    public static function readUInt32LE(array $data, int $offset): int
    {
        return $data[$offset] | ($data[$offset + 1] << 8)
             | ($data[$offset + 2] << 16) | ($data[$offset + 3] << 24);
    }

    public static function readUInt32BE(array $data, int $offset): int
    {
        return ($data[$offset] << 24) | ($data[$offset + 1] << 16)
             | ($data[$offset + 2] << 8) | $data[$offset + 3];
    }

    public static function writeUInt16LE(int $value): string
    {
        return chr($value & 0xFF) . chr(($value >> 8) & 0xFF);
    }

    public static function writeUInt16BE(int $value): string
    {
        return chr(($value >> 8) & 0xFF) . chr($value & 0xFF);
    }

    public static function writeUInt32LE(int $value): string
    {
        return chr($value & 0xFF) . chr(($value >> 8) & 0xFF)
             . chr(($value >> 16) & 0xFF) . chr(($value >> 24) & 0xFF);
    }

    public static function writeUInt32BE(int $value): string
    {
        return chr(($value >> 24) & 0xFF) . chr(($value >> 16) & 0xFF)
             . chr(($value >> 8) & 0xFF) . chr($value & 0xFF);
    }

    public static function readFixedString(array $data, int $offset, int $length): string
    {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $byte = $data[$offset + $i] ?? 0;
            if ($byte === 0) break;
            $str .= chr($byte);
        }
        return $str;
    }

    public static function writeFixedString(string $str, int $length): string
    {
        $result = str_pad($str, $length, "\x00");
        return substr($result, 0, $length);
    }

    public static function bytesToHex(array $bytes): string
    {
        $hex = '';
        foreach ($bytes as $byte) {
            $hex .= sprintf('%02X ', $byte);
        }
        return trim($hex);
    }

    public static function stringToBytes(string $str): array
    {
        return array_values(unpack('C*', $str));
    }

    /**
     * Konvertiert eine Zahl in BCD-Bytes
     */
    public static function decimalToBcd(int $number, int $bytes): array
    {
        $bcd = [];
        $str = str_pad((string)$number, $bytes * 2, '0', STR_PAD_LEFT);
        for ($i = 0; $i < $bytes * 2; $i += 2) {
            $bcd[] = hexdec(substr($str, $i, 2));
        }
        return $bcd;
    }

    /**
     * Konvertiert BCD-Bytes in eine Zahl
     */
    public static function bcdToDecimal(array $bytes): int
    {
        $str = '';
        foreach ($bytes as $byte) {
            $str .= sprintf('%02x', $byte);
        }
        return (int)$str;
    }

    /**
     * Kodiert ein Passwort im Anviz-Format (3 Bytes)
     * Bits 7-4 des ersten Bytes enthalten die Passwortlänge
     */
    public static function encodePassword(?int $password): array
    {
        if ($password === null) {
            return [0xFF, 0xFF, 0xFF];
        }
        
        $pwdStr = (string)$password;
        $pwdLen = strlen($pwdStr);
        
        $res = [0, 0, 0];
        $res[2] = $password & 0xFF;
        $tempPwd = $password >> 8;
        $res[1] = $tempPwd & 0xFF;
        $tempPwd >>= 8;
        $res[0] = ($pwdLen << 4) | ($tempPwd & 0x0F);
        
        return $res;
    }

    /**
     * Dekodiert ein Passwort aus dem Anviz-Format (3 Bytes)
     */
    public static function decodePassword(array $pwdBytes): ?int
    {
        if ($pwdBytes[0] === 0xFF && $pwdBytes[1] === 0xFF && $pwdBytes[2] === 0xFF) {
            return null;
        }
        
        $res = (int)($pwdBytes[0] & 0x0F);
        $res = ($res << 8) | $pwdBytes[1];
        $res = ($res << 8) | $pwdBytes[2];
        
        return $res;
    }
}

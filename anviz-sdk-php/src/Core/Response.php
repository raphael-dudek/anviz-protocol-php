<?php

namespace Anviz\SDK\Core;

use Anviz\SDK\Utils\CRC16;
use Anviz\SDK\Utils\BinaryHelper;
use Exception;

/**
 * Response-Klasse für Anviz-Antworten
 */
class Response
{
    private $deviceId;
    private $command;
    private $ret;
    private $dataLength;
    private $data;
    private $crc;
    private $valid;

    public function __construct(string $rawData)
    {
        $bytes = array_values(unpack('C*', $rawData));
        $this->parse($bytes);
    }

    private function parse(array $bytes): void
    {
        $length = count($bytes);
        if ($length < 8) { // Mindestgröße Preamble(1)+ID(4)+ACK(1)+Len(2)=8
            throw new Exception("Response zu kurz: $length bytes");
        }

        if ($bytes[0] !== Message::PREAMBLE) {
            throw new Exception(sprintf("Ungültiges Preamble: 0x%02X", $bytes[0]));
        }

        // Device ID (Bytes 1-4)
        $this->deviceId = ($bytes[1] << 24) | ($bytes[2] << 16) | ($bytes[3] << 8) | $bytes[4];
        $this->command = $bytes[5]; // ACK / ResponseCode

        $offset = 6;
        if ($this->command < 0x80) {
            $this->ret = 0;
        } else {
            $this->ret = $bytes[$offset++];
        }

        if ($offset + 2 > $length) {
             throw new Exception("Response zu kurz für Längenfeld");
        }

        $this->dataLength = ($bytes[$offset] << 8) | $bytes[$offset + 1];
        $offset += 2;

        if ($offset + $this->dataLength + 2 > $length) {
            // Manchmal kommen Pakete unvollständig an oder wir haben falsch geparst
            throw new Exception(sprintf("Datenlänge passt nicht: Erwartet %d, verfügbar %d", $this->dataLength, $length - $offset - 2));
        }

        $this->data = array_slice($bytes, $offset, $this->dataLength);
        $crcPos = $offset + $this->dataLength;

        $this->crc = $bytes[$crcPos] | ($bytes[$crcPos + 1] << 8);
        // Falls wir mehr Bytes haben als erwartet, ist das okay (solange der CRC davor passt oder am Ende)
        // Aber wir sollten den CRC dort prüfen wo er laut Header sein sollte.
        $this->valid = CRC16::validate($bytes, $crcPos + 2);
    }

    public function getDeviceId(): int { return $this->deviceId; }
    public function getCommand(): int { return $this->command; }
    public function getRet(): int { return $this->ret; }
    public function getData(): array { return $this->data; }
    public function getDataString(): string {
        $str = '';
        foreach ($this->data as $byte) {
            $str .= chr($byte);
        }
        return $str;
    }
    public function getDataLength(): int { return $this->dataLength; }
    public function isValid(): bool { return $this->valid; }
    public function isSuccess(): bool { return $this->valid && $this->ret === 0; }
}

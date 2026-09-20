<?php

namespace Anviz\SDK\Core;

use Anviz\SDK\Utils\CRC16;
use Anviz\SDK\Utils\BinaryHelper;

/**
 * Basis-Nachrichtenklasse für Anviz-Protokoll
 * 
 * Nachrichtenformat:
 * [Preamble] [DeviceID] [Command] [RET] [Length] [Data] [CRC16]
 * 0xA5       4 bytes     1 byte    1 byte 2 bytes  N bytes 2 bytes
 */
abstract class Message
{
    const PREAMBLE = 0xA5;
    const HEADER_SIZE = 8;
    const CRC_SIZE = 2;

    protected $deviceId;
    protected $command;
    protected $payload;
    protected $responseCode;

    public function __construct(int $deviceId)
    {
        $this->deviceId = $deviceId;
        $this->payload = [];
    }

    protected function buildPayload(int $command, string $data): void
    {
        $this->responseCode = ($command + 0x80) & 0xFF;
        $dataLength = strlen($data);
        
        // Gemäß C# Referenz (Command.cs)
        // payloadLength = 8 + dataLength + 2 (Preamble(1), ID(4), Cmd(1), Len(2) + Data + CRC(2))
        $payloadLength = 8 + $dataLength + 2;
        
        $payload = array_fill(0, $payloadLength, 0);
        $i = 0;

        $payload[$i++] = self::PREAMBLE;
        
        // Device ID ist immer 4-Byte Big-Endian (laut Bytes.cs und Command.cs)
        $payload[$i++] = ($this->deviceId >> 24) & 0xFF;
        $payload[$i++] = ($this->deviceId >> 16) & 0xFF;
        $payload[$i++] = ($this->deviceId >> 8) & 0xFF;
        $payload[$i++] = $this->deviceId & 0xFF;
        
        $payload[$i++] = $command & 0xFF;

        // RET Feld nur wenn CMD > 0x80 (gemäß C# SDK Command.cs)
        // Ausnahme: Wir senden RET = 0 nur für Pong o.ä. wenn wir als Gerät agieren würden.
        // In Requests vom SDK zum Gerät gibt es kein RET Feld vor Length.
        
        $payload[$i++] = ($dataLength >> 8) & 0xFF;
        $payload[$i++] = $dataLength & 0xFF;

        for ($j = 0; $j < $dataLength; $j++) {
            $payload[$i++] = ord($data[$j]);
        }

        $crc = CRC16::compute($payload, $payloadLength - 2);
        $payload[$i++] = $crc & 0xFF;
        $payload[$i++] = ($crc >> 8) & 0xFF;

        $this->payload = $payload;
    }

    public function getPayload(): array { return $this->payload; }
    public function getPayloadString(): string {
        $bytes = '';
        foreach ($this->payload as $byte) {
            $bytes .= chr($byte);
        }
        return $bytes;
    }
    public function getDeviceId(): int { return $this->deviceId; }
    public function getResponseCode(): int { return $this->responseCode; }
}

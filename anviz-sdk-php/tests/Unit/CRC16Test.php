<?php

namespace Anviz\SDK\Tests\Unit;

use Anviz\SDK\Utils\CRC16;
use PHPUnit\Framework\TestCase;

class CRC16Test extends TestCase
{
    public function testComputeCRC16()
    {
        // Test mit bekanntem Datensatz
        $data = [0xA5, 0x00, 0x00, 0x00, 0x01, 0x7F];
        $crc = CRC16::compute($data, count($data));

        $this->assertIsInt($crc);
        $this->assertGreaterThanOrEqual(0, $crc);
        $this->assertLessThanOrEqual(0xFFFF, $crc);
    }

    public function testValidateCRC16()
    {
        $data = [0xA5, 0x00, 0x00, 0x00, 0x01, 0x7F];
        $crc = CRC16::compute($data, count($data));

        // Füge CRC ans Ende
        $dataWithCrc = array_merge($data, [
            $crc & 0xFF,
            ($crc >> 8) & 0xFF
        ]);

        $this->assertTrue(CRC16::validate($dataWithCrc, count($dataWithCrc)));
    }

    public function testInvalidCRC16()
    {
        $data = [0xA5, 0x00, 0x00, 0x00, 0x01, 0x7F, 0xFF, 0xFF];
        $this->assertFalse(CRC16::validate($data, count($data)));
    }
}

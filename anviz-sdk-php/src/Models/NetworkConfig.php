<?php

namespace Anviz\SDK\Models;

use Anviz\SDK\Utils\BinaryHelper;

/**
 * Netzwerkkonfigurations-Modell
 */
class NetworkConfig
{
    private $ipAddress;
    private $subnetMask;
    private $gateway;
    private $serverIp;
    private $serverPort;
    private $macAddress;

    public static function fromByteArray(array $data): self
    {
        $config = new self();
        $offset = 0;

        if (count($data) >= 27) {
            $config->ipAddress = sprintf('%d.%d.%d.%d',
                $data[0] ?? 0, $data[1] ?? 0, $data[2] ?? 0, $data[3] ?? 0);
            $config->subnetMask = sprintf('%d.%d.%d.%d',
                $data[4] ?? 0, $data[5] ?? 0, $data[6] ?? 0, $data[7] ?? 0);
            $config->macAddress = sprintf('%02X:%02X:%02X:%02X:%02X:%02X',
                $data[8] ?? 0, $data[9] ?? 0, $data[10] ?? 0,
                $data[11] ?? 0, $data[12] ?? 0, $data[13] ?? 0);
            $config->gateway = sprintf('%d.%d.%d.%d',
                $data[14] ?? 0, $data[15] ?? 0, $data[16] ?? 0, $data[17] ?? 0);
            $config->serverIp = sprintf('%d.%d.%d.%d',
                $data[18] ?? 0, $data[19] ?? 0, $data[20] ?? 0, $data[21] ?? 0);
            $config->serverPort = BinaryHelper::readUInt16BE($data, 23);
            return $config;
        }

        if (count($data) >= 4) {
            $config->ipAddress = sprintf('%d.%d.%d.%d',
                $data[$offset] ?? 0, $data[$offset + 1] ?? 0,
                $data[$offset + 2] ?? 0, $data[$offset + 3] ?? 0);
            $offset += 4;
        }

        if (count($data) >= $offset + 4) {
            $config->subnetMask = sprintf('%d.%d.%d.%d',
                $data[$offset] ?? 0, $data[$offset + 1] ?? 0,
                $data[$offset + 2] ?? 0, $data[$offset + 3] ?? 0);
            $offset += 4;
        }

        if (count($data) >= $offset + 4) {
            $config->gateway = sprintf('%d.%d.%d.%d',
                $data[$offset] ?? 0, $data[$offset + 1] ?? 0,
                $data[$offset + 2] ?? 0, $data[$offset + 3] ?? 0);
            $offset += 4;
        }

        if (count($data) >= $offset + 4) {
            $config->serverIp = sprintf('%d.%d.%d.%d',
                $data[$offset] ?? 0, $data[$offset + 1] ?? 0,
                $data[$offset + 2] ?? 0, $data[$offset + 3] ?? 0);
            $offset += 4;
        }

        if (count($data) >= $offset + 2) {
            $config->serverPort = BinaryHelper::readUInt16BE($data, $offset);
            $offset += 2;
        }

        if (count($data) >= $offset + 6) {
            $config->macAddress = sprintf('%02X:%02X:%02X:%02X:%02X:%02X',
                $data[$offset] ?? 0, $data[$offset + 1] ?? 0, $data[$offset + 2] ?? 0,
                $data[$offset + 3] ?? 0, $data[$offset + 4] ?? 0, $data[$offset + 5] ?? 0);
        }

        return $config;
    }

    public function getIpAddress(): string { return $this->ipAddress ?? '0.0.0.0'; }
    public function getSubnetMask(): string { return $this->subnetMask ?? '0.0.0.0'; }
    public function getGateway(): string { return $this->gateway ?? '0.0.0.0'; }
    public function getServerIp(): string { return $this->serverIp ?? '0.0.0.0'; }
    public function getServerPort(): int { return $this->serverPort ?? 0; }
    public function getMacAddress(): string { return $this->macAddress ?? '00:00:00:00:00:00'; }
}

<?php

namespace Anviz\SDK\Core;

/**
 * Request-Klasse für Anviz-Befehle
 */
class Request extends Message
{
    public function __construct(int $deviceId, int $command, string $data = '')
    {
        parent::__construct($deviceId);
        $this->command = $command;
        $this->buildPayload($command, $data);
    }
}

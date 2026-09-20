<?php

namespace Anviz\SDK\Communication;

use Anviz\SDK\Utils\Logger;
use Exception;

/**
 * TCP-Client für Netzwerkkommunikation
 */
class TcpClient
{
    private $host;
    private $port;
    private $timeout;
    private $socket;
    private $connected;

    public function __construct(string $host, int $port, int $timeout = 10)
    {
        if (!extension_loaded('sockets')) {
            throw new Exception("Die PHP-Erweiterung 'sockets' ist nicht geladen. Bitte aktivieren Sie diese in Ihrer php.ini (extension=sockets).");
        }
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->socket = null;
        $this->connected = false;
    }

    public function connect(): bool
    {
        if ($this->connected) return true;

        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            throw new Exception("Socket-Erstellung fehlgeschlagen: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, 
                         ['sec' => $this->timeout, 'usec' => 0]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, 
                         ['sec' => $this->timeout, 'usec' => 0]);

        $result = @socket_connect($this->socket, $this->host, $this->port);

        if ($result === false) {
            $error = socket_last_error($this->socket);
            $this->disconnect();
            throw new Exception("Verbindung zu {$this->host}:{$this->port} fehlgeschlagen: " . socket_strerror($error));
        }

        // Kurze Pause nach dem Connect, damit der Socket bereit ist
        usleep(100000); // 100ms

        $this->connected = true;
        return true;
    }

    public function send(string $data): int
    {
        if (!$this->connected) {
            throw new Exception("Nicht verbunden");
        }

        $length = strlen($data);
        Logger::debug("TCP Send (" . $length . " bytes): " . bin2hex($data));
        $sent = @socket_write($this->socket, $data, $length);

        if ($sent === false) {
            throw new Exception("Senden fehlgeschlagen");
        }

        return $sent;
    }

    public function receive(int $maxLength = 4096): string
    {
        if (!$this->connected) {
            throw new Exception("Nicht verbunden");
        }

        $data = '';
        $startTime = microtime(true);
        $headerReadTimeout = $this->timeout;
        
        // Versuche mindestens den Header (8 Bytes) zu lesen
        while (strlen($data) < 8) {
            $chunk = @socket_read($this->socket, 8 - strlen($data), PHP_BINARY_READ);
            
            if ($chunk === false) {
                $errorCode = socket_last_error($this->socket);
                // 11 = EAGAIN, 10060 = ETIMEDOUT
                if ($errorCode === 11 || $errorCode === 10060) {
                    if (microtime(true) - $startTime >= $headerReadTimeout) {
                        Logger::debug("TCP Receive Timeout (No Header after {$headerReadTimeout}s). Bytes received: " . strlen($data) . " (" . bin2hex($data) . ")");
                        return $data;
                    }
                    usleep(50000); // 50ms warten
                    continue;
                }
                throw new Exception("Lesen vom Socket fehlgeschlagen: " . socket_strerror($errorCode));
            }
            
            if ($chunk === '') {
                // Verbindung wurde geschlossen
                $this->connected = false;
                Logger::debug("TCP Connection closed by peer while waiting for header. Received so far: " . bin2hex($data));
                return $data;
            }
            
            $data .= $chunk;
        }

        // Wenn wir den Header haben, können wir die restliche Länge bestimmen
        $bytes = array_values(unpack('C*', $data));
        $cmd = $bytes[5];
        $offset = 6;
        
        // RET Feld nur wenn CMD >= 0x80 (Antwort)
        if ($cmd >= 0x80) {
            // Wir müssen noch ein Byte für RET lesen, falls es noch nicht im Puffer ist
            if (strlen($data) === 8) {
                $retChunk = @socket_read($this->socket, 1, PHP_BINARY_READ);
                if ($retChunk !== false && $retChunk !== '') {
                    $data .= $retChunk;
                    $bytes[] = ord($retChunk);
                }
            }
            $offset = 7;
        }
        
        if (count($bytes) < $offset + 2) {
             // Wir brauchen noch die Längenbytes
             $lenChunk = @socket_read($this->socket, ($offset + 2) - count($bytes), PHP_BINARY_READ);
             if ($lenChunk !== false && $lenChunk !== '') {
                 $data .= $lenChunk;
                 $bytes = array_values(unpack('C*', $data));
             }
        }

        $dataLen = ($bytes[$offset] << 8) | $bytes[$offset+1];
        $totalExpected = $offset + 2 + $dataLen + 2; // Header + Data + CRC

        Logger::debug("TCP Header received. Expected total: $totalExpected bytes (CMD: 0x" . dechex($cmd) . ", DataLen: $dataLen)");

        while (strlen($data) < $totalExpected) {
             $chunk = @socket_read($this->socket, $totalExpected - strlen($data), PHP_BINARY_READ);
             if ($chunk === false) {
                 $errorCode = socket_last_error($this->socket);
                 if ($errorCode === 11 || $errorCode === 10060) {
                     if (microtime(true) - $startTime >= $this->timeout) {
                         Logger::debug("TCP Receive Timeout while reading payload");
                         break;
                     }
                     usleep(10000);
                     continue;
                 }
                 break;
             }
             if ($chunk === '') {
                 Logger::debug("TCP Connection closed while reading payload");
                 break;
             }
             $data .= $chunk;
        }

        Logger::debug("TCP Receive Total: " . bin2hex($data));
        return $data;
    }

    public function disconnect(): void
    {
        if ($this->socket !== null && is_resource($this->socket)) {
            @socket_close($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}

<?php

namespace Anviz\SDK\Models;

use Anviz\SDK\Utils\BinaryHelper;
use Anviz\SDK\Utils\Fingers;

/**
 * Benutzerinformations-Modell
 */
class UserInfo
{
    private $userId;
    private $name;
    private $password;
    private $cardId;
    private $department;
    private $group;
    private $mode;
    private $pwdh8;
    private $keep;
    private $message;
    private $enrolledFingers = [];

    public function __construct()
    {
        $this->userId = 0;
        $this->name = '';
        $this->password = '';
        $this->cardId = 0;
        $this->department = 1;
        $this->group = 1;
        $this->mode = 6;
        $this->pwdh8 = 0xFF;
        $this->keep = 0xFF;
        $this->message = 0x40;
    }

    public static function fromByteArray(array $data, int $offset = 0): self
    {
        $user = new self();

        // ID (5 Bytes)
        $user->userId = (
            ($data[$offset] << 32) | 
            ($data[$offset+1] << 24) | 
            ($data[$offset+2] << 16) | 
            ($data[$offset+3] << 8) | 
            $data[$offset+4]
        );
        $offset += 5;

        // Passwort (3 Bytes Anviz Format)
        $user->password = BinaryHelper::decodePassword(array_slice($data, $offset, 3));
        $offset += 3;

        // Card ID (4 Bytes)
        $user->cardId = (
            ($data[$offset] << 24) | 
            ($data[$offset+1] << 16) | 
            ($data[$offset+2] << 8) | 
            $data[$offset+3]
        );
        if ($user->cardId === 0xffffffff) $user->cardId = 0;
        $offset += 4;

        // Name (20 Bytes Unicode BE)
        $nameBytes = array_slice($data, $offset, 20);
        $user->name = self::decodeUnicodeBE($nameBytes);
        $offset += 20;

        $user->department = $data[$offset++];
        $user->group = $data[$offset++];
        $user->mode = $data[$offset++];
        
        // Enrolled Fingers (2 Bytes Bitmask)
        $fingerMask = ($data[$offset] << 8) | $data[$offset+1];
        $user->enrolledFingers = Fingers::decodeFingers($fingerMask);
        $offset += 2;

        $user->pwdh8 = $data[$offset++];
        $user->keep = $data[$offset++];
        $user->message = $data[$offset++];

        return $user;
    }

    public static function parseMultiple(array $data): array
    {
        $users = [];
        $recordSize = 40; 
        $count = (int)(count($data) / $recordSize);

        for ($i = 0; $i < $count; $i++) {
            $offset = $i * $recordSize;
            if ($offset + $recordSize <= count($data)) {
                $users[] = self::fromByteArray($data, $offset);
            }
        }

        return $users;
    }

    public function toByteString(): string
    {
        $data = '';

        // ID (5 Bytes)
        $id = $this->userId;
        $data .= chr(($id >> 32) & 0xFF);
        $data .= chr(($id >> 24) & 0xFF);
        $data .= chr(($id >> 16) & 0xFF);
        $data .= chr(($id >> 8) & 0xFF);
        $data .= chr($id & 0xFF);

        // Passwort (3 Bytes Anviz Format)
        $pwdBytes = BinaryHelper::encodePassword($this->password ? (int)$this->password : null);
        foreach ($pwdBytes as $b) $data .= chr($b);

        // Card ID (4 Bytes)
        $card = $this->cardId ?: 0xffffffff;
        $data .= chr(($card >> 24) & 0xFF);
        $data .= chr(($card >> 16) & 0xFF);
        $data .= chr(($card >> 8) & 0xFF);
        $data .= chr($card & 0xFF);

        // Name (20 Bytes Unicode BE)
        $name = self::encodeUnicodeBE($this->name);
        $name = self::truncateUtf16Be($name, 20);
        $data .= str_pad($name, 20, "\0");

        $data .= chr($this->department);
        $data .= chr($this->group);
        $data .= chr($this->mode);
        
        // Enrolled Fingers (2 Bytes)
        $fingerMask = Fingers::encodeFingers($this->enrolledFingers);
        $data .= chr(($fingerMask >> 8) & 0xFF);
        $data .= chr($fingerMask & 0xFF);

        $data .= chr($this->pwdh8);
        $data .= chr($this->keep);
        $data .= chr($this->message);

        // Füll-Bytes bis 40 (sollte hier bereits 40 sein)
        if (strlen($data) < 40) {
            $data = str_pad($data, 40, "\0");
        }

        return $data;
    }

    private static function decodeUnicodeBE(array $bytes): string
    {
        $str = '';
        foreach ($bytes as $byte) $str .= chr($byte);
        if (function_exists('mb_convert_encoding')) {
            return trim(mb_convert_encoding($str, 'UTF-8', 'UTF-16BE'), "\0");
        }
        if (function_exists('iconv')) {
            $converted = iconv('UTF-16BE', 'UTF-8//IGNORE', $str);
            if ($converted !== false) {
                return trim($converted, "\0");
            }
        }
        $bytes = array_values(unpack('C*', $str));
        $latin1 = '';
        for ($i = 0; $i + 1 < count($bytes); $i += 2) {
            $hi = $bytes[$i];
            $lo = $bytes[$i + 1];
            if ($hi === 0 && $lo === 0) {
                continue;
            }
            if ($hi === 0) {
                $latin1 .= chr($lo);
            } else {
                $latin1 .= '?';
            }
        }
        return trim(utf8_encode($latin1), "\0");
    }

    private static function encodeUnicodeBE(string $text): string
    {
        if (!preg_match('//u', $text)) {
            $text = utf8_encode($text);
        }
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($text, 'UTF-16BE');
        }
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'UTF-16BE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }
        $latin1 = utf8_decode($text);
        $out = '';
        $len = strlen($latin1);
        for ($i = 0; $i < $len; $i++) {
            $out .= "\0" . $latin1[$i];
        }
        return $out;
    }

    private static function truncateUtf16Be(string $data, int $maxBytes): string
    {
        $maxBytes = max(0, $maxBytes);
        if (strlen($data) <= $maxBytes) {
            return $data;
        }
        $truncated = substr($data, 0, $maxBytes);
        if ((strlen($truncated) % 2) !== 0) {
            $truncated = substr($truncated, 0, -1);
        }
        return $truncated;
    }

    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): void { $this->userId = $userId; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getPassword(): ?string { return $this->password ? (string)$this->password : null; }
    public function setPassword(?string $password): void { $this->password = $password; }

    public function getCardId(): int { return $this->cardId; }
    public function setCardId(int $cardId): void { $this->cardId = $cardId; }

    public function getDepartment(): int { return $this->department; }
    public function setDepartment(int $department): void { $this->department = $department; }

    public function getGroup(): int { return $this->group; }
    public function setGroup(int $group): void { $this->group = $group; }

    public function getMode(): int { return $this->mode; }
    public function setMode(int $mode): void { $this->mode = $mode; }

    public function getEnrolledFingers(): array { return $this->enrolledFingers; }
    public function setEnrolledFingers(array $fingers): void { $this->enrolledFingers = $fingers; }

    public function getPwdh8(): int { return $this->pwdh8; }
    public function setPwdh8(int $val): void { $this->pwdh8 = $val; }

    public function getKeep(): int { return $this->keep; }
    public function setKeep(int $val): void { $this->keep = $val; }

    public function getMessage(): int { return $this->message; }
    public function setMessage(int $val): void { $this->message = $val; }

    public function isAdmin(): bool { 
        // Beim Anviz Protokoll ist oft Keep=2 ein Admin
        return $this->keep === 2 || $this->keep === 0x02; 
    }
    public function setAdmin(bool $admin): void { 
        $this->keep = $admin ? 2 : 1; 
    }
}
